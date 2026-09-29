<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USERNAME', fields: ['username'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    public const GROUP_ADMIN = 0;
    public const GROUP_TRANSLATOR = 1;
    public const GROUP_TRANSLATION_REVIEWER = 2;
    public const GROUP_PARATEXT_AUTHOR = 3;
    public const GROUP_PARATEXT_REVIEWER = 4;

    public const GROUP_LABELS = [
        self::GROUP_ADMIN => 'Administrador',
        self::GROUP_TRANSLATOR => 'Tradutor',
        self::GROUP_TRANSLATION_REVIEWER => 'Revisor de Tradução',
        self::GROUP_PARATEXT_AUTHOR => 'Autor de Paratextos',
        self::GROUP_PARATEXT_REVIEWER => 'Revisor de Paratextos',
    ];

    /**
     * Grupo "principal" (legado). Mantido em sincronia com $workGroups:
     * sempre o menor grupo da lista. Use hasWorkGroup()/isAdmin() para checagens.
     */
    // Padrão NÃO-admin: antes era 0, o que tornava administrador qualquer usuário criado sem grupo explícito.
    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    private int $workGroup = self::GROUP_TRANSLATOR;

    /**
     * Todos os grupos/atividades do usuário (um usuário pode ter vários).
     * Null em registros antigos: nesse caso vale apenas $workGroup.
     *
     * @var list<int>|null
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $workGroups = null;

    /** Quando o e-mail com o link ATUAL (convite/redefinição) foi entregue ao servidor de e-mail. Null = ainda não enviado. */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $passwordEmailSentAt = null;

    /** Quando o usuário definiu a própria senha pela última vez (null = convite pendente). */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $passwordSetAt = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getWorkGroup(): int
    {
        return $this->workGroup;
    }

    /**
     * Define um único grupo (substitui todos os outros).
     */
    public function setWorkGroup(int $workGroup): static
    {
        return $this->setWorkGroups([$workGroup]);
    }

    /**
     * @return list<int>
     */
    public function getWorkGroups(): array
    {
        if ($this->workGroups === null || $this->workGroups === []) {
            return [$this->workGroup];
        }

        return $this->workGroups;
    }

    /**
     * @param iterable<int|string> $workGroups
     */
    public function setWorkGroups(iterable $workGroups): static
    {
        $groups = [];
        foreach ($workGroups as $group) {
            $group = (int) $group;
            if (isset(self::GROUP_LABELS[$group])) {
                $groups[$group] = $group;
            }
        }

        if ($groups === []) {
            // Lista vazia é tratada pela validação do formulário (Count min 1);
            // aqui apenas não alteramos o grupo principal.
            $this->workGroups = [];

            return $this;
        }

        ksort($groups);
        $this->workGroups = array_values($groups);
        $this->workGroup = $this->workGroups[0];

        return $this;
    }

    public function addWorkGroup(int $workGroup): static
    {
        return $this->setWorkGroups([...$this->getWorkGroups(), $workGroup]);
    }

    public function hasWorkGroup(int $workGroup): bool
    {
        return in_array($workGroup, $this->getWorkGroups(), true);
    }

    public function isAdmin(): bool
    {
        return $this->hasWorkGroup(self::GROUP_ADMIN);
    }

    /**
     * @return list<string>
     */
    public function getWorkGroupLabels(): array
    {
        return array_map(static fn (int $g) => self::GROUP_LABELS[$g] ?? 'Grupo ' . $g, $this->getWorkGroups());
    }

    public function getPasswordSetAt(): ?\DateTimeImmutable
    {
        return $this->passwordSetAt;
    }

    public function setPasswordSetAt(?\DateTimeImmutable $passwordSetAt): static
    {
        $this->passwordSetAt = $passwordSetAt;

        return $this;
    }

    public function getPasswordEmailSentAt(): ?\DateTimeImmutable
    {
        return $this->passwordEmailSentAt;
    }

    public function setPasswordEmailSentAt(?\DateTimeImmutable $passwordEmailSentAt): static
    {
        $this->passwordEmailSentAt = $passwordEmailSentAt;

        return $this;
    }

    /** True enquanto a pessoa ainda não criou a própria senha (convite pendente). */
    public function isInvitationPending(): bool
    {
        return $this->passwordSetAt === null;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        if ($this->isAdmin()) {
            $roles[] = 'ROLE_ADMIN';
        }

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $resetTokenExpiresAt = null;

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): static
    {
        $this->resetToken = $resetToken;

        return $this;
    }

    public function getResetTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->resetTokenExpiresAt;
    }

    public function setResetTokenExpiresAt(?\DateTimeImmutable $resetTokenExpiresAt): static
    {
        $this->resetTokenExpiresAt = $resetTokenExpiresAt;

        return $this;
    }

    public function isResetTokenValid(): bool
    {
        return $this->resetToken !== null 
            && $this->resetTokenExpiresAt !== null 
            && $this->resetTokenExpiresAt > new \DateTimeImmutable();
    }

    public function clearResetToken(): static
    {
        $this->resetToken = null;
        $this->resetTokenExpiresAt = null;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }
}
