<?php

namespace App\Service;

use App\Entity\User;

/**
 * Emite os tokens de definição/redefinição de senha.
 *
 * - Convite (usuário novo, ainda sem senha própria): válido por 72h.
 * - Redefinição normal ("esqueci minha senha" / reenvio pelo admin): válido por 2h.
 */
class PasswordTokenService
{
    public const INVITATION_TTL_HOURS = 72;
    public const RESET_TTL_HOURS = 2;

    public const TYPE_INVITATION = 'invitation';
    public const TYPE_RESET = 'reset';

    public function issueInvitationToken(User $user): string
    {
        return $this->issue($user, self::INVITATION_TTL_HOURS);
    }

    public function issueResetToken(User $user): string
    {
        return $this->issue($user, self::RESET_TTL_HOURS);
    }

    /**
     * Escolhe o tipo certo: quem nunca criou senha recebe convite (72h), os demais redefinição (2h).
     *
     * @return self::TYPE_*
     */
    public function issueForUser(User $user): string
    {
        if ($user->isInvitationPending()) {
            $this->issueInvitationToken($user);

            return self::TYPE_INVITATION;
        }

        $this->issueResetToken($user);

        return self::TYPE_RESET;
    }

    /**
     * Evita reenvios em sequência (ex.: alguém clicando várias vezes em "esqueci minha senha").
     */
    public function wasIssuedRecently(User $user, int $minutes = 5): bool
    {
        $expiresAt = $user->getResetTokenExpiresAt();
        if ($user->getResetToken() === null || $expiresAt === null) {
            return false;
        }

        $ttl = $user->isInvitationPending() ? self::INVITATION_TTL_HOURS : self::RESET_TTL_HOURS;
        $issuedAt = $expiresAt->modify(sprintf('-%d hours', $ttl));

        return $issuedAt > new \DateTimeImmutable(sprintf('-%d minutes', $minutes));
    }

    private function issue(User $user, int $hoursValid): string
    {
        $token = bin2hex(random_bytes(32));
        $user->setResetToken($token);
        $user->setResetTokenExpiresAt(new \DateTimeImmutable(sprintf('+%d hours', $hoursValid)));
        $user->setPasswordEmailSentAt(null); // o link novo ainda não foi enviado

        return $token;
    }
}
