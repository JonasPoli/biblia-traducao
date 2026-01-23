<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{
    // Translations
    public const CAN_EDIT_TRANSLATION = 'CAN_EDIT_TRANSLATION';
    public const CAN_COMMENT_TRANSLATION = 'CAN_COMMENT_TRANSLATION';

    // Paratexts
    public const CAN_EDIT_PARATEXT = 'CAN_EDIT_PARATEXT';
    public const CAN_COMMENT_PARATEXT = 'CAN_COMMENT_PARATEXT';

    // Global
    public const CAN_EXPORT = 'CAN_EXPORT';
    public const CAN_VIEW_GLOBAL_REFERENCES = 'CAN_VIEW_GLOBAL_REFERENCES';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // if the attribute isn't one we support, return false
        return in_array($attribute, [
            self::CAN_EDIT_TRANSLATION,
            self::CAN_COMMENT_TRANSLATION,
            self::CAN_EDIT_PARATEXT,
            self::CAN_COMMENT_PARATEXT,
            self::CAN_EXPORT,
            self::CAN_VIEW_GLOBAL_REFERENCES,
        ]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }

        // Admin (0) has access to everything
        if ($user->getWorkGroup() === 0) {
            return true;
        }

        return match ($attribute) {
            self::CAN_EDIT_TRANSLATION => $this->canEditTranslation($user),
            self::CAN_COMMENT_TRANSLATION => $this->canCommentTranslation($user),
            self::CAN_EDIT_PARATEXT => $this->canEditParatext($user),
            self::CAN_COMMENT_PARATEXT => $this->canCommentParatext($user),
            self::CAN_EXPORT => $this->canExport($user),
            self::CAN_VIEW_GLOBAL_REFERENCES => $this->canViewGlobalReferences($user),
            default => false,
        };
    }

    private function canEditTranslation(User $user): bool
    {
        // Only Translator (1) and Admin (0 - handled above)
        return $user->getWorkGroup() === 1;
    }

    private function canCommentTranslation(User $user): bool
    {
        // Translation Reviewer (2)
        // Also Translator (1) might want to comment? 
        // Spec says: Reviewer -> Translator flow.
        return $user->getWorkGroup() === 2;
    }

    private function canEditParatext(User $user): bool
    {
        // Paratext Author (3)
        return $user->getWorkGroup() === 3;
    }

    private function canCommentParatext(User $user): bool
    {
        // Paratext Reviewer (4)
        return $user->getWorkGroup() === 4;
    }

    private function canExport(User $user): bool
    {
        // Translator (1) can export
        return $user->getWorkGroup() === 1;
    }

    private function canViewGlobalReferences(User $user): bool
    {
        // Translator (1)
        return $user->getWorkGroup() === 1;
    }
}
