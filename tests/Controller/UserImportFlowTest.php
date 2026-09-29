<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Fluxo completo: importação pelo admin, convite (72h), "esqueci minha senha" (2h) e definição de senha.
 * Requer um banco descartável (ex.: DATABASE_URL="sqlite:///%kernel.project_dir%/var/test.db" em .env.test.local).
 */
class UserImportFlowTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($metadata);
        $tool->createSchema($metadata);
    }

    private function createUser(string $email, array $groups, bool $passwordSet = true): User
    {
        $user = (new User())
            ->setName(ucfirst(strtok($email, '@')))
            ->setEmail($email)
            ->setUsername($email)
            ->setWorkGroups($groups)
            ->setPassword('x')
            ->setPasswordSetAt($passwordSet ? new \DateTimeImmutable('-1 day') : null);
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /** O EntityManager é resetado entre requisições: sempre relê do banco. */
    private function reload(string $email): User
    {
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        return static::getContainer()->get(UserRepository::class)->findOneBy(['email' => $email]);
    }

    /** Marca as caixas de grupo (uma por grupo, na ordem 0..4). */
    private function checkGroups(\Symfony\Component\DomCrawler\Form $form, array $groups): void
    {
        foreach ($form['user[workGroups]'] as $checkbox) {
            in_array((int) $checkbox->availableOptionValues()[0], $groups, true) ? $checkbox->tick() : $checkbox->untick();
        }
    }

    private function postImport(array $extra = []): void
    {
        $crawler = $this->client->request('GET', '/admin/user/import');
        $this->assertResponseIsSuccessful();
        $token = $crawler->filter('input[name="_csrf_token"]')->attr('value');

        $file = new UploadedFile(__DIR__ . '/../Fixtures/google_forms_respostas.csv', 'respostas.csv', 'text/csv', null, true);
        $this->client->request('POST', '/admin/user/import', ['_csrf_token' => $token, 'send_email' => '1'] + $extra, ['csv_file' => $file]);
        $this->assertResponseIsSuccessful();
    }

    public function testAdminUserPagesAndMultiGroupDashboard(): void
    {
        $multi = $this->createUser('multi@exemplo.com', [2, 3, 4]);
        $this->client->loginUser($this->createUser('admin@exemplo.com', [0]));

        $this->client->request('GET', '/admin/user/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Revisor Paratexto');

        $this->client->request('GET', '/admin/user/' . $multi->getId());
        $this->assertSelectorTextContains('body', 'Revisor de Tradução, Autor de Paratextos, Revisor de Paratextos');

        // Edição preserva vários grupos
        $crawler = $this->client->request('GET', '/admin/user/' . $multi->getId() . '/edit');
        $this->assertResponseIsSuccessful();
        $form = $crawler->filter('form[name="user"]')->form();
        $this->checkGroups($form, [3, 4]);
        $this->client->submit($form);
        $this->assertResponseRedirects();
        $this->assertSame([3, 4], $this->reload('multi@exemplo.com')->getWorkGroups());

        // Cadastro manual sem senha → convite por e-mail
        $crawler = $this->client->request('GET', '/admin/user/new');
        $form = $crawler->filter('form[name="user"]')->form([
            'user[name]' => 'Manual', 'user[email]' => 'manual@exemplo.com', 'user[username]' => 'manual@exemplo.com',
        ]);
        $this->checkGroups($form, [3]);
        $this->client->submit($form);
        $this->assertResponseRedirects();
        $this->assertEmailCount(1);
        $this->assertTrue($this->reload('manual@exemplo.com')->isInvitationPending());

        $this->client->loginUser($this->reload('multi@exemplo.com'));
        $this->client->request('GET', '/admin');
        $this->assertResponseIsSuccessful();
    }

    public function testNonAdminCannotOpenImport(): void
    {
        $this->client->loginUser($this->createUser('autor@exemplo.com', [3, 4]));
        $this->client->request('GET', '/admin/user/import');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testImportCreatesNewUsersSkipsExistingAndSendsInvitations(): void
    {
        $this->client->loginUser($this->createUser('admin@exemplo.com', [0]));
        // Já cadastrado (com outro grupo): deve ser ignorado e não receber e-mail
        $this->createUser('caio@exemplo.com', [1]);

        $this->postImport();

        $repo = static::getContainer()->get(UserRepository::class);

        $bia = $repo->findOneBy(['email' => 'bia.duas@exemplo.com']);
        $this->assertNotNull($bia);
        $this->assertSame([2, 3, 4], $bia->getWorkGroups());
        $this->assertSame('bia.duas@exemplo.com', $bia->getUsername());
        $this->assertTrue($bia->isInvitationPending());
        $this->assertFalse($bia->isAdmin());
        $this->assertEqualsWithDelta((new \DateTimeImmutable('+72 hours'))->getTimestamp(), $bia->getResetTokenExpiresAt()->getTimestamp(), 30);

        $this->assertSame([3], $repo->findOneBy(['email' => 'ana.criadora@exemplo.com'])->getWorkGroups());
        $this->assertSame([1], $repo->findOneBy(['email' => 'caio@exemplo.com'])->getWorkGroups(), 'existente não é alterado');
        $this->assertNull($repo->findOneBy(['email' => 'dani@exemplo.com']), 'sem atividade reconhecida → erro, não cadastra');

        // Ana e Bia recebem convite; Caio (existente), Dani e o e-mail inválido não
        $this->assertEmailCount(2);
        $email = $this->getMailerMessage(0);
        $this->assertEmailSubjectContains($email, 'Crie sua senha');
        $this->assertEmailHtmlBodyContains($email, '72 horas');
        $this->assertEmailHtmlBodyContains($email, '/reset-password/');

        $this->assertSelectorTextContains('body', 'Ignorado (Já existe)');

        // Envio acontece depois da resposta e fica registrado
        $this->assertNotNull($this->reload('bia.duas@exemplo.com')->getPasswordEmailSentAt());
    }

    public function testResendPendingInvitationsRecoversInterruptedImport(): void
    {
        $this->client->loginUser($this->createUser('admin@exemplo.com', [0]));
        // Simula importação interrompida: usuário criado, convite nunca enviado
        $this->createUser('orfao@exemplo.com', [3], passwordSet: false);
        // Convite já entregue e ainda válido: não deve receber de novo
        $ok = $this->createUser('ok@exemplo.com', [4], passwordSet: false);
        $ok->setResetToken('t-ok')->setResetTokenExpiresAt(new \DateTimeImmutable('+10 hours'))->setPasswordEmailSentAt(new \DateTimeImmutable());
        $this->em->flush();

        $crawler = $this->client->request('GET', '/admin/user/');
        $this->assertSelectorTextContains('body', 'Enviar convites pendentes (1)');
        $this->assertSelectorTextContains('body', 'E-mail ainda não enviado');

        $this->client->submit($crawler->filter('form[action$="resend-pending-invitations"]')->form());
        $this->assertResponseRedirects('/admin/user/');
        $this->assertEmailCount(1);
        $this->assertEmailAddressContains($this->getMailerMessage(0), 'to', 'orfao@exemplo.com');

        $orfao = $this->reload('orfao@exemplo.com');
        $this->assertNotNull($orfao->getPasswordEmailSentAt());
        $this->assertEqualsWithDelta((new \DateTimeImmutable('+72 hours'))->getTimestamp(), $orfao->getResetTokenExpiresAt()->getTimestamp(), 30);
        $this->assertSame('t-ok', $this->reload('ok@exemplo.com')->getResetToken());
    }

    public function testDryRunWritesNothing(): void
    {
        $this->client->loginUser($this->createUser('admin@exemplo.com', [0]));
        $this->postImport(['dry_run' => '1']);

        $this->assertSame(1, static::getContainer()->get(UserRepository::class)->count([]));
        $this->assertEmailCount(0);
        $this->assertSelectorTextContains('body', 'Simulação');
    }

    public function testForgotPasswordSendsTwoHourLinkAndDoesNotRevealAccounts(): void
    {
        $user = $this->createUser('leitor@exemplo.com', [3]);

        $crawler = $this->client->request('GET', '/forgot-password');
        $token = $crawler->filter('input[name="_csrf_token"]')->attr('value');

        $this->client->request('POST', '/forgot-password', ['_csrf_token' => $token, 'email' => 'LEITOR@exemplo.com']);
        $this->assertResponseIsSuccessful();
        $this->assertEmailCount(1);
        $this->assertEmailSubjectContains($this->getMailerMessage(0), 'Redefinição de Senha');
        $this->assertEmailHtmlBodyContains($this->getMailerMessage(0), '2 horas');

        $user = $this->reload($user->getEmail());
        $this->assertEqualsWithDelta((new \DateTimeImmutable('+2 hours'))->getTimestamp(), $user->getResetTokenExpiresAt()->getTimestamp(), 30);
        $okPage = $this->client->getResponse()->getContent();

        // E-mail inexistente: mesma resposta, nenhum e-mail
        $this->client->request('POST', '/forgot-password', ['_csrf_token' => $token, 'email' => 'ninguem@exemplo.com']);
        $this->assertEmailCount(0);
        $this->assertSelectorTextContains('h1', 'Verifique seu e-mail');
        $this->assertStringContainsString('Verifique seu e-mail', $okPage);
    }

    public function testResetLinkSetsPasswordAndExpiredLinkIsRejected(): void
    {
        $user = $this->createUser('novo@exemplo.com', [3], passwordSet: false);
        $user->setResetToken('tok-valido')->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
        $this->em->flush();

        $crawler = $this->client->request('GET', '/reset-password/tok-valido');
        $this->assertSelectorTextContains('h1', 'Crie sua Senha');
        $csrf = $crawler->filter('input[name="_csrf_token"]')->attr('value');

        $this->client->request('POST', '/reset-password/tok-valido', [
            '_csrf_token' => $csrf, 'password' => 'segredo123', 'confirm_password' => 'segredo123',
        ]);
        $this->assertResponseRedirects('/login');

        $user = $this->reload($user->getEmail());
        $this->assertFalse($user->isInvitationPending());
        $this->assertNull($user->getResetToken(), 'link só pode ser usado uma vez');

        $user->setResetToken('tok-velho')->setResetTokenExpiresAt(new \DateTimeImmutable('-1 minute'));
        $this->em->flush();
        $this->client->request('GET', '/reset-password/tok-velho');
        $this->assertSelectorTextContains('h1', 'Link Inválido ou Expirado');
    }
}
