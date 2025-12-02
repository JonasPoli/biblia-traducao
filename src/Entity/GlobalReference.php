<?php

namespace App\Entity;

use App\Repository\GlobalReferenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GlobalReferenceRepository::class)]
class GlobalReference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $term = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $referenceText = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTerm(): ?string
    {
        return $this->term;
    }

    public function setTerm(string $term): static
    {
        $this->term = $term;

        return $this;
    }

    public function getReferenceText(): ?string
    {
        return $this->referenceText;
    }

    public function setReferenceText(string $referenceText): static
    {
        $this->referenceText = $referenceText;

        return $this;
    }
}
