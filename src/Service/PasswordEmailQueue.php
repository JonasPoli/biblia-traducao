<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Fila de e-mails de convite/redefinição enviados DEPOIS da resposta HTTP.
 *
 * Enviar vários e-mails dentro da requisição de importação estourava o tempo
 * do gateway (504). Aqui a página responde na hora e o PHP-FPM continua
 * enviando em segundo plano (kernel.terminate roda após fastcgi_finish_request).
 * O resultado de cada envio fica em User::passwordEmailSentAt e no log.
 */
class PasswordEmailQueue implements EventSubscriberInterface
{
    /** @var array<int, array{user: User, type: ?string}> */
    private array $pending = [];

    public function __construct(
        private readonly AuthEmailService $authEmailService,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'flush',
            ConsoleEvents::TERMINATE => 'flush',
        ];
    }

    public function queue(User $user, ?string $type = null): void
    {
        $this->pending[spl_object_id($user)] = ['user' => $user, 'type' => $type];
    }

    public function count(): int
    {
        return count($this->pending);
    }

    /**
     * Envia tudo o que estiver na fila.
     *
     * @return array{sent: int, failed: int}
     */
    public function flush(): array
    {
        if ($this->pending === []) {
            return ['sent' => 0, 'failed' => 0];
        }

        if (\function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $sent = 0;
        $failed = 0;
        $pending = $this->pending;
        $this->pending = [];

        foreach ($pending as ['user' => $user, 'type' => $type]) {
            if ($this->authEmailService->sendPasswordResetEmail($user, null, $type)) {
                $sent++;
            } else {
                $failed++;
            }

            // Grava a cada envio: se o processo for interrompido, o que já saiu fica registrado
            if ($this->entityManager->isOpen()) {
                try {
                    $this->entityManager->flush();
                } catch (\Throwable $e) {
                    $this->logger->error('Falha ao registrar envio de e-mail', ['error' => $e->getMessage()]);
                }
            }
        }

        $this->logger->info('Fila de e-mails de senha processada', ['sent' => $sent, 'failed' => $failed]);

        return ['sent' => $sent, 'failed' => $failed];
    }
}
