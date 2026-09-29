<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Service\PasswordTokenService;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testNewUserIsNotAdminByDefault(): void
    {
        $user = new User();

        $this->assertFalse($user->isAdmin());
        $this->assertNotContains('ROLE_ADMIN', $user->getRoles());
        $this->assertTrue($user->isInvitationPending());
    }

    public function testMultipleWorkGroups(): void
    {
        $user = (new User())->setWorkGroups([4, 3, 3, 99]);

        $this->assertSame([3, 4], $user->getWorkGroups());
        $this->assertSame(3, $user->getWorkGroup(), 'grupo principal = menor grupo');
        $this->assertTrue($user->hasWorkGroup(4));
        $this->assertFalse($user->hasWorkGroup(2));
        $this->assertFalse($user->isAdmin());

        $user->addWorkGroup(2);
        $this->assertSame([2, 3, 4], $user->getWorkGroups());
    }

    public function testAdminGroupGrantsAdminRole(): void
    {
        $user = (new User())->setWorkGroups([0, 1]);

        $this->assertTrue($user->isAdmin());
        $this->assertContains('ROLE_ADMIN', $user->getRoles());
    }

    public function testTokenLifetimes(): void
    {
        $tokens = new PasswordTokenService();
        $user = new User();

        $this->assertSame(PasswordTokenService::TYPE_INVITATION, $tokens->issueForUser($user));
        $this->assertEqualsWithDelta((new \DateTimeImmutable('+72 hours'))->getTimestamp(), $user->getResetTokenExpiresAt()->getTimestamp(), 5);
        $this->assertTrue($tokens->wasIssuedRecently($user));

        $user->setPasswordSetAt(new \DateTimeImmutable());
        $this->assertSame(PasswordTokenService::TYPE_RESET, $tokens->issueForUser($user));
        $this->assertEqualsWithDelta((new \DateTimeImmutable('+2 hours'))->getTimestamp(), $user->getResetTokenExpiresAt()->getTimestamp(), 5);
    }
}
