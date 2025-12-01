<?php

namespace App\Entity;

use App\Repository\StrongDefinitionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StrongDefinitionRepository::class)]
class StrongDefinition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private ?string $code = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $hebrewWord = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $greekWord = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $transliteration = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $fullDefinition = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $definition = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lemma = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pronunciation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getHebrewWord(): ?string
    {
        return $this->hebrewWord;
    }

    public function setHebrewWord(?string $hebrewWord): static
    {
        $this->hebrewWord = $hebrewWord;

        return $this;
    }

    public function getGreekWord(): ?string
    {
        return $this->greekWord;
    }

    public function setGreekWord(?string $greekWord): static
    {
        $this->greekWord = $greekWord;

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

    public function getFullDefinition(): ?string
    {
        return $this->fullDefinition;
    }

    public function setFullDefinition(?string $fullDefinition): static
    {
        $this->fullDefinition = $fullDefinition;

        return $this;
    }

    public function getDefinition(): ?string
    {
        return $this->definition;
    }

    public function setDefinition(?string $definition): static
    {
        $this->definition = $definition;

        return $this;
    }

    public function getLemma(): ?string
    {
        return $this->lemma;
    }

    public function setLemma(?string $lemma): static
    {
        $this->lemma = $lemma;

        return $this;
    }

    public function getPronunciation(): ?string
    {
        return $this->pronunciation;
    }

    public function setPronunciation(?string $pronunciation): static
    {
        $this->pronunciation = $pronunciation;

        return $this;
    }
}
