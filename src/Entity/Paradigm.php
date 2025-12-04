<?php

namespace App\Entity;

use App\Repository\ParadigmRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParadigmRepository::class)]
class Paradigm
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $foreignWord = null;

    #[ORM\Column(length: 255)]
    private ?string $translation = null;

    #[ORM\Column(length: 50)]
    private ?string $strongCode = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $rmac = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $wordClass = null;

    #[ORM\Column]
    private ?int $amount = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getForeignWord(): ?string
    {
        return $this->foreignWord;
    }

    public function setForeignWord(string $foreignWord): static
    {
        $this->foreignWord = $foreignWord;

        return $this;
    }

    public function getTranslation(): ?string
    {
        return $this->translation;
    }

    public function setTranslation(string $translation): static
    {
        $this->translation = $translation;

        return $this;
    }

    public function getStrongCode(): ?string
    {
        return $this->strongCode;
    }

    public function setStrongCode(string $strongCode): static
    {
        $this->strongCode = $strongCode;

        return $this;
    }

    public function getRmac(): ?string
    {
        return $this->rmac;
    }

    public function setRmac(?string $rmac): static
    {
        $this->rmac = $rmac;

        return $this;
    }

    public function getWordClass(): ?string
    {
        return $this->wordClass;
    }

    public function setWordClass(?string $wordClass): static
    {
        $this->wordClass = $wordClass;

        return $this;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): static
    {
        $this->amount = $amount;

        return $this;
    }
}
