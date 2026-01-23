<?php

namespace App\Entity;

use App\Repository\LouwNidaDomainRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LouwNidaDomainRepository::class)]
class LouwNidaDomain
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $domainNumber = null;

    #[ORM\Column]
    private ?int $subdomainNumber = null;

    #[ORM\Column(length: 255)]
    private ?string $category = null;

    #[ORM\Column(length: 50)]
    private ?string $semanticDomainId = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $greekExample = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $glossEnglish = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $glossPortuguese = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDomainNumber(): ?int
    {
        return $this->domainNumber;
    }

    public function setDomainNumber(int $domainNumber): static
    {
        $this->domainNumber = $domainNumber;

        return $this;
    }

    public function getSubdomainNumber(): ?int
    {
        return $this->subdomainNumber;
    }

    public function setSubdomainNumber(int $subdomainNumber): static
    {
        $this->subdomainNumber = $subdomainNumber;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getSemanticDomainId(): ?string
    {
        return $this->semanticDomainId;
    }

    public function setSemanticDomainId(string $semanticDomainId): static
    {
        $this->semanticDomainId = $semanticDomainId;

        return $this;
    }

    public function getGreekExample(): ?string
    {
        return $this->greekExample;
    }

    public function setGreekExample(?string $greekExample): static
    {
        $this->greekExample = $greekExample;

        return $this;
    }

    public function getGlossEnglish(): ?string
    {
        return $this->glossEnglish;
    }

    public function setGlossEnglish(?string $glossEnglish): static
    {
        $this->glossEnglish = $glossEnglish;

        return $this;
    }

    public function getGlossPortuguese(): ?string
    {
        return $this->glossPortuguese;
    }

    public function setGlossPortuguese(?string $glossPortuguese): static
    {
        $this->glossPortuguese = $glossPortuguese;

        return $this;
    }
}
