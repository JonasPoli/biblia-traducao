<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserImportService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly AuthEmailService $authEmailService
    ) {
    }

    /**
     * Generate and set a new reset token on a user.
     */
    public function generateResetToken(User $user, int $hoursValid = 48): string
    {
        $token = bin2hex(random_bytes(32));
        $user->setResetToken($token);
        $user->setResetTokenExpiresAt((new \DateTimeImmutable())->modify("+{$hoursValid} hours"));

        return $token;
    }

    /**
     * Parse raw CSV string or file content into normalized array of records.
     *
     * @return array<int, array{name: string, email: string, workGroup: int, raw: array}>
     */
    public function parseCsv(string $csvContent): array
    {
        // Remove UTF-8 BOM if present
        $csvContent = preg_replace('/^\xEF\xBB\xBF/', '', trim($csvContent));
        if (empty($csvContent)) {
            return [];
        }

        // Detect line endings
        $lines = preg_split('/\r\n|\r|\n/', $csvContent);
        if (empty($lines)) {
            return [];
        }

        // Detect delimiter (, or ;) from first line
        $firstLine = $lines[0];
        $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

        $parsedRows = [];
        $headerMap = null;

        foreach ($lines as $index => $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $columns = str_getcsv($line, $delimiter);
            if (empty($columns) || (count($columns) === 1 && trim($columns[0]) === '')) {
                continue;
            }

            // Detect headers on first row
            if ($headerMap === null) {
                $isHeader = false;
                $tempMap = [];
                foreach ($columns as $colIdx => $colName) {
                    $cleanHeader = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $colName)));
                    if (in_array($cleanHeader, ['nome', 'name', 'fullname', 'nomecompleto'])) {
                        $tempMap['name'] = $colIdx;
                        $isHeader = true;
                    } elseif (in_array($cleanHeader, ['email', 'email', 'correio', 'mail', 'usuario', 'user'])) {
                        $tempMap['email'] = $colIdx;
                        $isHeader = true;
                    } elseif (in_array($cleanHeader, ['grupodetrabalho', 'grupo', 'workgroup', 'gt', 'group', 'permissao'])) {
                        $tempMap['workGroup'] = $colIdx;
                        $isHeader = true;
                    }
                }

                if ($isHeader && isset($tempMap['email'])) {
                    $headerMap = $tempMap;
                    continue; // Skip header row
                }

                // If not identified as header, use default 0: name, 1: email, 2: workGroup
                $headerMap = ['name' => 0, 'email' => 1, 'workGroup' => 2];
            }

            $name = isset($headerMap['name'], $columns[$headerMap['name']]) ? trim($columns[$headerMap['name']]) : '';
            $email = isset($headerMap['email'], $columns[$headerMap['email']]) ? trim($columns[$headerMap['email']]) : '';
            $rawGroup = isset($headerMap['workGroup'], $columns[$headerMap['workGroup']]) ? trim($columns[$headerMap['workGroup']]) : '';

            // Fallback if positional
            if ($name === '' && isset($columns[0])) {
                $name = trim($columns[0]);
            }
            if ($email === '' && isset($columns[1])) {
                $email = trim($columns[1]);
            }
            if ($rawGroup === '' && isset($columns[2])) {
                $rawGroup = trim($columns[2]);
            }

            if ($email !== '') {
                $parsedRows[] = [
                    'name' => $name ?: $email,
                    'email' => strtolower($email),
                    'workGroup' => $this->normalizeWorkGroup($rawGroup),
                    'raw' => $columns,
                ];
            }
        }

        return $parsedRows;
    }

    /**
     * Map string or integer group representation to valid WorkGroup integer (0-4).
     */
    public function normalizeWorkGroup(string|int $group): int
    {
        if (is_numeric($group)) {
            $val = (int) $group;
            return ($val >= 0 && $val <= 4) ? $val : 1;
        }

        $clean = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', (string) $group)));

        if (str_contains($clean, 'admin')) {
            return 0;
        }
        if (str_contains($clean, 'revisorparatexto') || str_contains($clean, 'revparatexto')) {
            return 4;
        }
        if (str_contains($clean, 'autor') || str_contains($clean, 'paratexto')) {
            return 3;
        }
        if (str_contains($clean, 'revisortrad') || str_contains($clean, 'revisor')) {
            return 2;
        }
        if (str_contains($clean, 'tradut')) {
            return 1;
        }

        return 1; // Default: Tradutor
    }

    /**
     * Import users from parsed rows.
     *
     * @param array<int, array{name: string, email: string, workGroup: int, raw: array}> $rows
     * @return array{total: int, created: array, updated: array, skipped: array, errors: array, emails_sent: int}
     */
    public function importUsers(array $rows, bool $sendEmail = true, bool $overwrite = false): array
    {
        $created = [];
        $updated = [];
        $skipped = [];
        $errors = [];
        $emailsSent = 0;

        foreach ($rows as $row) {
            $email = filter_var($row['email'], FILTER_VALIDATE_EMAIL);
            $name = $row['name'] ?: $email;
            $workGroup = $row['workGroup'];

            if (!$email) {
                $errors[] = [
                    'row' => $row,
                    'message' => "E-mail inválido: '{$row['email']}'",
                ];
                continue;
            }

            try {
                // Check if user already exists by email or username
                $existingUser = $this->userRepository->findOneBy(['email' => $email])
                    ?? $this->userRepository->findOneBy(['username' => $email]);

                if ($existingUser) {
                    if ($overwrite) {
                        $existingUser->setName($name);
                        $existingUser->setWorkGroup($workGroup);

                        $token = $this->generateResetToken($existingUser, 48);
                        $this->entityManager->flush();

                        $emailSuccess = false;
                        if ($sendEmail) {
                            $emailSuccess = $this->authEmailService->sendPasswordResetEmail($existingUser);
                            if ($emailSuccess) {
                                $emailsSent++;
                            }
                        }

                        $updated[] = [
                            'user' => $existingUser,
                            'email' => $email,
                            'name' => $name,
                            'workGroup' => $workGroup,
                            'emailSent' => $emailSuccess,
                        ];
                    } else {
                        $skipped[] = [
                            'email' => $email,
                            'name' => $existingUser->getName(),
                            'message' => 'Usuário já cadastrado no sistema',
                        ];
                    }
                    continue;
                }

                // Create new user
                $user = new User();
                $user->setName($name);
                $user->setEmail($email);
                $user->setUsername($email);
                $user->setWorkGroup($workGroup);

                // Set a secure temporary random password hash
                $tempPassword = bin2hex(random_bytes(16));
                $user->setPassword($this->passwordHasher->hashPassword($user, $tempPassword));

                // Generate reset/invitation token
                $token = $this->generateResetToken($user, 48);

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                $emailSuccess = false;
                if ($sendEmail) {
                    $emailSuccess = $this->authEmailService->sendPasswordResetEmail($user);
                    if ($emailSuccess) {
                        $emailsSent++;
                    }
                }

                $created[] = [
                    'user' => $user,
                    'email' => $email,
                    'name' => $name,
                    'workGroup' => $workGroup,
                    'emailSent' => $emailSuccess,
                    'token' => $token,
                ];
            } catch (\Throwable $e) {
                $errors[] = [
                    'row' => $row,
                    'message' => 'Erro ao processar usuário: ' . $e->getMessage(),
                ];
            }
        }

        return [
            'total' => count($rows),
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'emails_sent' => $emailsSent,
        ];
    }
}
