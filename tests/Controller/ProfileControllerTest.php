<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProfileControllerTest extends WebTestCase
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

    private function createUser(string $password = 'senhaAntiga1'): User
    {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user = (new User())
            ->setName('Maria Voluntária')
            ->setEmail('maria@exemplo.com')
            ->setUsername('maria@exemplo.com')
            ->setWorkGroups([3, 4])
            ->setPasswordSetAt(new \DateTimeImmutable('-1 day'));
        $user->setPassword($hasher->hashPassword($user, $password));
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function reload(): User
    {
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->em->clear();

        return static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'maria@exemplo.com']);
    }

    private function profileForm(): Form
    {
        $crawler = $this->client->request('GET', '/admin/perfil');
        $this->assertResponseIsSuccessful();

        return $crawler->selectButton('Salvar alterações')->form();
    }

    public function testRequiresLogin(): void
    {
        $this->client->request('GET', '/admin/perfil');
        $this->assertResponseRedirects('/login');
    }

    public function testShowsReadOnlyEmailAndGroupsWithoutEditableFields(): void
    {
        $this->client->loginUser($this->createUser());
        $crawler = $this->client->request('GET', '/admin/perfil');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'maria@exemplo.com');
        $this->assertSelectorTextContains('body', 'Autor de Paratextos');
        $this->assertSelectorTextContains('body', 'Revisor de Paratextos');
        $this->assertCount(0, $crawler->filter('[name^="profile[email]"], [name^="profile[workGroup"], [name^="profile[username]"]'));
        $this->assertCount(1, $crawler->filter('a[href="/admin/perfil"]'), 'link no menu');
    }

    public function testUpdatesPersonalDataWithoutTouchingPassword(): void
    {
        $user = $this->createUser();
        $originalHash = $user->getPassword();
        $this->client->loginUser($user);

        $form = $this->profileForm();
        $form->setValues([
            'profile[name]' => 'Maria da Silva',
            'profile[phone]' => '(16) 99999-1234',
            'profile[address]' => 'Rua das Flores, 100',
            'profile[city]' => 'Araraquara',
            'profile[state]' => 'SP',
            // navegador pode ter autopreenchido a senha atual; sem nova senha, deve ser ignorada
            'profile[currentPassword]' => 'qualquer-coisa',
        ]);
        $this->client->submit($form);
        $this->assertResponseRedirects('/admin/perfil');

        $user = $this->reload();
        $this->assertSame('Maria da Silva', $user->getName());
        $this->assertSame('(16) 99999-1234', $user->getPhone());
        $this->assertSame('Rua das Flores, 100', $user->getAddress());
        $this->assertSame('Araraquara', $user->getCity());
        $this->assertSame('SP', $user->getState());
        $this->assertSame($originalHash, $user->getPassword(), 'senha não muda se "Nova senha" ficar em branco');
    }

    public function testCannotChangeEmailOrGroupsByTamperingTheForm(): void
    {
        $this->client->loginUser($this->createUser());
        $form = $this->profileForm();
        $values = $form->getPhpValues();
        $values['profile']['email'] = 'hacker@exemplo.com';
        $values['profile']['workGroups'] = ['0'];
        $values['profile']['name'] = 'Outro Nome';

        $this->client->request('POST', '/admin/perfil', $values);
        $this->assertResponseStatusCodeSame(422);

        $user = $this->reload();
        $this->assertSame('maria@exemplo.com', $user->getEmail());
        $this->assertSame([3, 4], $user->getWorkGroups());
        $this->assertFalse($user->isAdmin());
        $this->assertSame('Maria Voluntária', $user->getName(), 'nada é salvo quando o formulário é adulterado');
    }

    public function testPasswordChangeRequiresCorrectCurrentPassword(): void
    {
        $user = $this->createUser();
        $originalHash = $user->getPassword();
        $this->client->loginUser($user);

        $form = $this->profileForm();
        $form->setValues([
            'profile[currentPassword]' => 'errada',
            'profile[newPassword][first]' => 'novaSenha123',
            'profile[newPassword][second]' => 'novaSenha123',
        ]);
        $this->client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('body', 'Senha atual incorreta');
        $this->assertSame($originalHash, $this->reload()->getPassword());

        $form = $this->profileForm();
        $form->setValues([
            'profile[currentPassword]' => 'senhaAntiga1',
            'profile[newPassword][first]' => 'novaSenha123',
            'profile[newPassword][second]' => 'outraCoisa',
        ]);
        $this->client->submit($form);
        $this->assertSelectorTextContains('body', 'A confirmação não confere');
        $this->assertSame($originalHash, $this->reload()->getPassword());
    }

    public function testPasswordChangeWorksAndKeepsUserLoggedIn(): void
    {
        $this->client->loginUser($this->createUser());

        $form = $this->profileForm();
        $form->setValues([
            'profile[currentPassword]' => 'senhaAntiga1',
            'profile[newPassword][first]' => 'novaSenha123',
            'profile[newPassword][second]' => 'novaSenha123',
        ]);
        $this->client->submit($form);
        $this->assertResponseRedirects('/admin/perfil');

        $user = $this->reload();
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $this->assertTrue($hasher->isPasswordValid($user, 'novaSenha123'));

        // continua logado depois de trocar a senha
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Sua senha foi alterada');
    }

    public function testUploadAndRemovePhoto(): void
    {
        $this->client->loginUser($this->createUser());
        $avatarDir = static::getContainer()->getParameter('kernel.project_dir') . '/public/uploads/avatars';

        $png = tempnam(sys_get_temp_dir(), 'avatar') . '.png';
        $img = imagecreatetruecolor(20, 20);
        imagepng($img, $png);

        $form = $this->profileForm();
        $form['profile[imageFile]']->upload($png);
        $this->client->submit($form);
        $this->assertResponseRedirects('/admin/perfil');

        $image = $this->reload()->getImage();
        $this->assertNotNull($image);
        $this->assertFileExists($avatarDir . '/' . $image);

        // Arquivo que não é imagem é recusado
        $txt = tempnam(sys_get_temp_dir(), 'fake') . '.png';
        file_put_contents($txt, 'não sou imagem');
        $form = $this->profileForm();
        $form['profile[imageFile]']->upload($txt);
        $this->client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame($image, $this->reload()->getImage());

        $form = $this->profileForm();
        $form['profile[removeImage]']->tick();
        $this->client->submit($form);
        $this->assertResponseRedirects('/admin/perfil');
        $this->assertNull($this->reload()->getImage());
        $this->assertFileDoesNotExist($avatarDir . '/' . $image);
    }
}
