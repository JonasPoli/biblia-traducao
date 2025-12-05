<?php

namespace App\Entity;

use App\Repository\VerseWordRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VerseWordRepository::class)]
class VerseWord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Verse $verse = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?StrongDefinition $strongDefinition = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $strongCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $wordOriginal = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $wordPortuguese = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $wordEnglish = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $transliteration = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $englishType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $portugueseType = null;

    #[ORM\Column]
    private ?int $position = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVerse(): ?Verse
    {
        return $this->verse;
    }

    public function setVerse(?Verse $verse): static
    {
        $this->verse = $verse;

        return $this;
    }

    public function getStrongDefinition(): ?StrongDefinition
    {
        return $this->strongDefinition;
    }

    public function setStrongDefinition(?StrongDefinition $strongDefinition): static
    {
        $this->strongDefinition = $strongDefinition;

        return $this;
    }

    public function getStrongCode(): ?string
    {
        return $this->strongCode;
    }

    public function setStrongCode(?string $strongCode): static
    {
        $this->strongCode = $strongCode;

        return $this;
    }

    public function getWordOriginal(): ?string
    {
        return $this->wordOriginal;
    }

    public function setWordOriginal(?string $wordOriginal): static
    {
        $this->wordOriginal = $wordOriginal;

        return $this;
    }

    public function getWordPortuguese(): ?string
    {
        return $this->wordPortuguese;
    }

    public function setWordPortuguese(?string $wordPortuguese): static
    {
        $this->wordPortuguese = $wordPortuguese;

        return $this;
    }

    public function getWordEnglish(): ?string
    {
        return $this->wordEnglish;
    }

    public function setWordEnglish(?string $wordEnglish): static
    {
        $this->wordEnglish = $wordEnglish;

        return $this;
    }

    public function getTransliteration(): ?string
    {
        return $this->transliteration;
    }

    public function setTransliteration(?string $transliteration): static
    {
        $this->transliteration = $transliteration;

        return $this;
    }

    public function getEnglishType(): ?string
    {
        return $this->englishType;
    }

    public function setEnglishType(?string $englishType): static
    {
        $this->englishType = $englishType;

        return $this;
    }

    public function getPortugueseType(): ?string
    {
        return $this->portugueseType;
    }

    public function setPortugueseType(?string $portugueseType): static
    {
        $this->portugueseType = $portugueseType;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }
}
