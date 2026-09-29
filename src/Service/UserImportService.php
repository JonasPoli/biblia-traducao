<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use function Symfony\Component\String\u;

/**
 * Importação de usuários a partir de CSV.
 *
 * Aceita tanto o modelo próprio (Nome, Email, Grupo de Trabalho) quanto a exportação
 * do Google Forms "Plano de trabalho" (Carimbo de data/hora, Endereço de e-mail,
 * Nome completo, "Escolha uma ou mais opções para equipe (s) de trabalho", ...).
 *
 * - Várias atividades por célula, separadas por vírgula.
 * - O mesmo e-mail em várias linhas é consolidado: as atividades são somadas.
 * - E-mail já cadastrado: é ignorado e listado no relatório.
 * - Usuário novo: recebe convite para criar a senha (link válido por 72h).
 *   Os e-mails vão para PasswordEmailQueue e saem depois da resposta HTTP.
 */
class UserImportService
{
    /** Atividades do formulário → grupo de trabalho. */
    private const ACTIVITY_MAP = [
        'criarparatexto' => User::GROUP_PARATEXT_AUTHOR,
        'conferirparatexto' => User::GROUP_PARATEXT_REVIEWER,
        'revisarparatexto' => User::GROUP_PARATEXT_REVIEWER,
        'conferirtraducao' => User::GROUP_TRANSLATION_REVIEWER,
        'revisartraducao' => User::GROUP_TRANSLATION_REVIEWER,
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly PasswordEmailQueue $emailQueue,
        private readonly PasswordTokenService $passwordTokenService,
    ) {
    }

    /**
     * Lê o CSV e devolve um registro por e-mail (duplicados já consolidados).
     *
     * @return array<int, array{name: string, email: string, workGroups: list<int>, lines: list<int>, rawActivities: list<string>, unknownActivities: list<string>}>
     */
    public function parseCsv(string $csvContent): array
    {
        $csvContent = preg_replace('/^\xEF\xBB\xBF/', '', $csvContent);
        if (trim($csvContent) === '') {
            return [];
        }

        if (!mb_check_encoding($csvContent, 'UTF-8')) {
            // Excel em português costuma salvar em Windows-1252
            $csvContent = mb_convert_encoding($csvContent, 'UTF-8', 'Windows-1252');
        }

        $firstLine = strtok($csvContent, "\r\n") ?: '';
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $csvContent);
        rewind($handle);

        $headerMap = null;
        $lineNumber = 0;
        /** @var array<string, array> $byEmail */
        $byEmail = [];

        while (($columns = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
            $lineNumber++;
            if ($columns === [null] || implode('', array_map('trim', array_map('strval', $columns))) === '') {
                continue;
            }

            if ($headerMap === null) {
                $detected = $this->detectHeader($columns);
                if ($detected !== null) {
                    $headerMap = $detected;
                    continue;
                }
                // Sem cabeçalho reconhecível: formato posicional Nome, Email, Grupo
                $headerMap = ['name' => 0, 'email' => 1, 'workGroup' => 2];
            }

            $email = mb_strtolower(trim((string) ($columns[$headerMap['email']] ?? '')));
            if ($email === '') {
                continue;
            }

            $name = trim(preg_replace('/\s+/u', ' ', (string) ($columns[$headerMap['name'] ?? -1] ?? '')));
            $rawActivity = trim((string) ($columns[$headerMap['workGroup'] ?? -1] ?? ''));
            [$groups, $unknown] = $this->parseActivities($rawActivity);

            if (!isset($byEmail[$email])) {
                $byEmail[$email] = [
                    'name' => '',
                    'email' => $email,
                    'workGroups' => [],
                    'lines' => [],
                    'rawActivities' => [],
                    'unknownActivities' => [],
                ];
            }

            $record = &$byEmail[$email];
            if ($name !== '') {
                $record['name'] = $name; // a resposta mais recente (linha mais abaixo) define o nome
            }
            $record['workGroups'] = array_values(array_unique([...$record['workGroups'], ...$groups]));
            sort($record['workGroups']);
            $record['lines'][] = $lineNumber;
            if ($rawActivity !== '') {
                $record['rawActivities'][] = $rawActivity;
            }
            $record['unknownActivities'] = array_values(array_unique([...$record['unknownActivities'], ...$unknown]));
            unset($record);
        }

        fclose($handle);

        foreach ($byEmail as &$record) {
            if ($record['name'] === '') {
                $record['name'] = $record['email'];
            }
        }
        unset($record);

        return array_values($byEmail);
    }

    /**
     * Converte o texto de uma célula (ex.: "Criar paratextos, Conferir paratextos") em grupos.
     *
     * @return array{0: list<int>, 1: list<string>} [grupos reconhecidos, trechos não reconhecidos]
     */
    public function parseActivities(string $raw): array
    {
        $groups = [];
        $unknown = [];

        foreach (preg_split('/[,;|\/]+/', $raw) ?: [] as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $group = $this->normalizeActivity($part);
            if ($group === null) {
                $unknown[] = $part;
            } else {
                $groups[$group] = $group;
            }
        }

        ksort($groups);

        return [array_values($groups), $unknown];
    }

    /**
     * Uma atividade/grupo isolado → número do grupo, ou null se não reconhecido.
     */
    public function normalizeActivity(string|int $value): ?int
    {
        if (is_int($value) || ctype_digit(trim((string) $value))) {
            $val = (int) $value;

            return isset(User::GROUP_LABELS[$val]) ? $val : null;
        }

        $clean = $this->slug((string) $value);
        // plural → singular ("paratextos" → "paratexto")
        $clean = preg_replace('/s$/', '', $clean);

        if (isset(self::ACTIVITY_MAP[$clean])) {
            return self::ACTIVITY_MAP[$clean];
        }

        // Nomes do modelo antigo (Tradutor, Revisor de Tradução, Autor de Paratextos...)
        return match (true) {
            str_contains($clean, 'admin') => User::GROUP_ADMIN,
            str_contains($clean, 'paratexto') && (str_contains($clean, 'revis') || str_contains($clean, 'confer')) => User::GROUP_PARATEXT_REVIEWER,
            str_contains($clean, 'autor'), str_contains($clean, 'paratexto') => User::GROUP_PARATEXT_AUTHOR,
            str_contains($clean, 'revisor') => User::GROUP_TRANSLATION_REVIEWER,
            str_contains($clean, 'tradut') => User::GROUP_TRANSLATOR,
            default => null,
        };
    }

    /**
     * Cadastra os usuários ainda não existentes e envia o convite.
     *
     * @param array<int, array{name: string, email: string, workGroups: list<int>}> $rows
     * @return array{total: int, created: array, updated: array, skipped: array, errors: array, emails_queued: int, dry_run: bool}
     */
    public function importUsers(array $rows, bool $sendEmail = true, bool $overwrite = false, bool $dryRun = false): array
    {
        $created = [];
        $updated = [];
        $skipped = [];
        $errors = [];
        $emailsQueued = 0;

        foreach ($rows as $row) {
            $email = filter_var($row['email'], FILTER_VALIDATE_EMAIL);
            $name = $row['name'] ?: (string) $email;
            $groups = $row['workGroups'] ?? [];
            $warnings = !empty($row['unknownActivities'])
                ? ['Atividade não reconhecida e ignorada: ' . implode(', ', $row['unknownActivities'])]
                : [];

            if (!$email) {
                $errors[] = ['row' => $row, 'message' => "E-mail inválido: '{$row['email']}'"];
                continue;
            }

            if ($groups === []) {
                $errors[] = ['row' => $row, 'message' => 'Nenhuma atividade reconhecida (esperado: Criar paratextos, Conferir paratextos ou Conferir tradução).'];
                continue;
            }

            try {
                $existingUser = $this->userRepository->findOneByEmailOrUsername($email);

                if ($existingUser) {
                    if (!$overwrite) {
                        $skipped[] = [
                            'email' => $email,
                            'name' => $existingUser->getName(),
                            'workGroups' => $existingUser->getWorkGroups(),
                            'message' => 'Usuário já cadastrado no sistema',
                        ];
                        continue;
                    }

                    $emailQueued = false;
                    if (!$dryRun) {
                        $existingUser->setName($name);
                        $existingUser->setWorkGroups($groups);
                        $type = null;
                        if ($sendEmail) {
                            $type = $this->passwordTokenService->issueForUser($existingUser);
                        }
                        $this->entityManager->flush();

                        if ($sendEmail) {
                            $this->emailQueue->queue($existingUser, $type);
                            $emailQueued = true;
                            $emailsQueued++;
                        }
                    }

                    $updated[] = [
                        'email' => $email,
                        'name' => $name,
                        'workGroups' => $groups,
                        'emailQueued' => $emailQueued,
                        'warnings' => $warnings,
                    ];
                    continue;
                }

                $emailQueued = false;
                if (!$dryRun) {
                    $user = new User();
                    $user->setName($name);
                    $user->setEmail($email);
                    $user->setUsername($email);
                    $user->setWorkGroups($groups);
                    $user->setPasswordSetAt(null); // convite pendente

                    // Senha aleatória inutilizável até a pessoa criar a dela pelo link
                    $user->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(16))));
                    $this->passwordTokenService->issueInvitationToken($user);

                    $this->entityManager->persist($user);
                    $this->entityManager->flush();

                    if ($sendEmail) {
                        // Enviado depois da resposta HTTP (evita 504 com muitos usuários)
                        $this->emailQueue->queue($user, PasswordTokenService::TYPE_INVITATION);
                        $emailQueued = true;
                        $emailsQueued++;
                    }
                }

                $created[] = [
                    'email' => $email,
                    'name' => $name,
                    'workGroups' => $groups,
                    'emailQueued' => $emailQueued,
                    'warnings' => $warnings,
                ];
            } catch (\Throwable $e) {
                $errors[] = ['row' => $row, 'message' => 'Erro ao processar usuário: ' . $e->getMessage()];
                if (!$this->entityManager->isOpen()) {
                    break; // EntityManager fechado após erro de banco: não dá para continuar com segurança
                }
            }
        }

        return [
            'total' => count($rows),
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'emails_queued' => $emailsQueued,
            'dry_run' => $dryRun,
        ];
    }

    /**
     * @param list<string|null> $columns
     * @return array{name?: int, email: int, workGroup?: int}|null
     */
    private function detectHeader(array $columns): ?array
    {
        $map = [];
        foreach ($columns as $idx => $header) {
            $clean = $this->slug((string) $header);
            if ($clean === '') {
                continue;
            }

            if (!isset($map['email']) && (str_contains($clean, 'email') || in_array($clean, ['mail', 'correio', 'usuario', 'user'], true))) {
                $map['email'] = $idx;
            } elseif (!isset($map['name']) && (str_starts_with($clean, 'nome') || in_array($clean, ['name', 'fullname'], true))) {
                $map['name'] = $idx;
            } elseif (!isset($map['workGroup']) && (
                str_contains($clean, 'grupo') || str_contains($clean, 'equipe') || str_contains($clean, 'atividade')
                || str_contains($clean, 'workgroup') || str_contains($clean, 'permissao') || in_array($clean, ['gt', 'group'], true)
            )) {
                $map['workGroup'] = $idx;
            }
        }

        return isset($map['email']) ? $map : null;
    }

    private function slug(string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', u($value)->ascii()->lower()->toString());
    }
}
