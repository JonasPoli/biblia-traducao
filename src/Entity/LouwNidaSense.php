<?php

namespace App\Entity;

use App\Repository\LouwNidaSenseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LouwNidaSenseRepository::class)]
class LouwNidaSense
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $louwNidaNumber = null;

    #[ORM\Column(length: 20)]
    private ?string $strongCode = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $ideiaCentralPtBr = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $sentidoSemanticoPtBr = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLouwNidaNumber(): ?string
    {
        return $this->louwNidaNumber;
    }

    public function setLouwNidaNumber(string $louwNidaNumber): static
    {
        $this->louwNidaNumber = $louwNidaNumber;

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

    public function getIdeiaCentralPtBr(): ?string
    {
        return $this->ideiaCentralPtBr;
    }

    public function setIdeiaCentralPtBr(?string $ideiaCentralPtBr): static
    {
        $this->ideiaCentralPtBr = $ideiaCentralPtBr;

        return $this;
    }

    public function getSentidoSemanticoPtBr(): ?string
    {
        return $this->sentidoSemanticoPtBr;
    }

    public function setSentidoSemanticoPtBr(?string $sentidoSemanticoPtBr): static
    {
        $this->sentidoSemanticoPtBr = $sentidoSemanticoPtBr;

        return $this;
    }
}
