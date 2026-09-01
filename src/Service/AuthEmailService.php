<?php

namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AuthEmailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger,
        #[Autowire(env: 'EMAIL_FROM')]
        private readonly string $emailFrom = 'noreply@wab.com.br'
    ) {
    }

    public function getWorkGroupName(int $workGroup): string
    {
        return match ($workGroup) {
            0 => 'Administrador (Grupo 0)',
            1 => 'Tradutor (Grupo 1)',
            2 => 'Revisor de Tradução (Grupo 2)',
            3 => 'Autor de Paratextos (Grupo 3)',
            4 => 'Revisor de Paratextos (Grupo 4)',
            default => 'Grupo ' . $workGroup,
        };
    }

    /**
     * Send password definition/reset email to user.
     */
    public function sendPasswordResetEmail(User $user, ?string $customResetUrl = null): bool
    {
        if (!$user->getEmail()) {
            $this->logger->warning('Cannot send password reset email: user has no email', ['userId' => $user->getId()]);
            return false;
        }

        if (!$user->getResetToken()) {
            $this->logger->warning('Cannot send password reset email: user has no resetToken', ['userId' => $user->getId()]);
            return false;
        }

        try {
            $resetUrl = $customResetUrl ?? $this->urlGenerator->generate(
                'app_reset_password',
                ['token' => $user->getResetToken()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $email = (new TemplatedEmail())
                ->from(new Address($this->emailFrom, 'Tradução do Novo Testamento'))
                ->to(new Address($user->getEmail(), $user->getName() ?? $user->getEmail()))
                ->subject('Definição de Senha - Tradução do Novo Testamento')
                ->htmlTemplate('emails/password_reset_email.html.twig')
                ->context([
                    'user' => $user,
                    'resetUrl' => $resetUrl,
                    'workGroupName' => $this->getWorkGroupName($user->getWorkGroup()),
                ]);

            $this->mailer->send($email);
            $this->logger->info('Password reset email sent successfully', ['email' => $user->getEmail()]);

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send password reset email', [
                'email' => $user->getEmail(),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
