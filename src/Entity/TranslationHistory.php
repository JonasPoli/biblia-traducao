<?php

namespace App\Entity;

use App\Repository\TranslationHistoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TranslationHistoryRepository::class)]
class TranslationHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?VerseText $verseText = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $oldText = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVerseText(): ?VerseText
    {
        return $this->verseText;
    }

    public function setVerseText(?VerseText $verseText): static
    {
        $this->verseText = $verseText;

        return $this;
    }

    public function getOldText(): ?string
    {
        return $this->oldText;
    }

    public function setOldText(string $oldText): static
    {
        $this->oldText = $oldText;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
