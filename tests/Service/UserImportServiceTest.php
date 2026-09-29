<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\AuthEmailService;
use App\Service\PasswordTokenService;
use App\Service\UserImportService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserImportServiceTest extends TestCase
{
    private UserImportService $service;

    protected function setUp(): void
    {
        $this->service = new UserImportService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(UserRepository::class),
            $this->createMock(UserPasswordHasherInterface::class),
            $this->createMock(AuthEmailService::class),
            new PasswordTokenService(),
        );
    }

    public function testParsesGoogleFormsExportAndMergesDuplicates(): void
    {
        $rows = $this->service->parseCsv(file_get_contents(__DIR__ . '/../Fixtures/google_forms_respostas.csv'));
        $byEmail = array_column($rows, null, 'email');

        // 6 respostas, 5 e-mails distintos (Bia aparece duas vezes, com maiúsculas diferentes)
        $this->assertCount(5, $rows);

        $this->assertSame('Ana Criadora', $byEmail['ana.criadora@exemplo.com']['name']);
        $this->assertSame([User::GROUP_PARATEXT_AUTHOR], $byEmail['ana.criadora@exemplo.com']['workGroups']);

        // Soma das atividades das duas respostas; nome da resposta mais recente
        $this->assertSame(
            [User::GROUP_TRANSLATION_REVIEWER, User::GROUP_PARATEXT_AUTHOR, User::GROUP_PARATEXT_REVIEWER],
            $byEmail['bia.duas@exemplo.com']['workGroups']
        );
        $this->assertSame('Bia Duas Vezes Souza', $byEmail['bia.duas@exemplo.com']['name']);

        // Comentário com aspas e quebra de linha não quebra a leitura
        $this->assertSame('Caio Tres', $byEmail['caio@exemplo.com']['name']);
        $this->assertSame([2, 3, 4], $byEmail['caio@exemplo.com']['workGroups']);

        $this->assertSame([], $byEmail['dani@exemplo.com']['workGroups']);
        $this->assertSame(['Fazer café'], $byEmail['dani@exemplo.com']['unknownActivities']);
    }

    /**
     * @dataProvider activityProvider
     */
    public function testNormalizeActivity(string $input, ?int $expected): void
    {
        $this->assertSame($expected, $this->service->normalizeActivity($input));
    }

    public static function activityProvider(): iterable
    {
        yield ['Criar paratextos', User::GROUP_PARATEXT_AUTHOR];
        yield ['Conferir paratextos', User::GROUP_PARATEXT_REVIEWER];
        yield ['Conferir tradução', User::GROUP_TRANSLATION_REVIEWER];
        yield ['conferir traducao', User::GROUP_TRANSLATION_REVIEWER];
        yield ['Tradutor', User::GROUP_TRANSLATOR];
        yield ['Revisor de Tradução', User::GROUP_TRANSLATION_REVIEWER];
        yield ['Autor de Paratextos', User::GROUP_PARATEXT_AUTHOR];
        yield ['Revisor de Paratextos', User::GROUP_PARATEXT_REVIEWER];
        yield ['Administrador', User::GROUP_ADMIN];
        yield ['3', User::GROUP_PARATEXT_AUTHOR];
        yield ['9', null];
        yield ['Fazer café', null];
    }

    public function testLegacyTemplateStillWorks(): void
    {
        $csv = "Nome;Email;Grupo de Trabalho\nJoão;JOAO@x.com;Tradutor\nMaria;maria@x.com;\"Criar paratextos, Conferir tradução\"\n";
        $rows = $this->service->parseCsv($csv);

        $this->assertCount(2, $rows);
        $this->assertSame('joao@x.com', $rows[0]['email']);
        $this->assertSame([1], $rows[0]['workGroups']);
        $this->assertSame([2, 3], $rows[1]['workGroups']);
    }
}
