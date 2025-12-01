<?php

namespace App\Entity;

use App\Repository\BookRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookRepository::class)]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 10)]
    private ?string $abbreviation = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Testament $testament = null;

    #[ORM\Column]
    private ?int $bookOrder = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAbbreviation(): ?string
    {
        return $this->abbreviation;
    }

    public function setAbbreviation(string $abbreviation): static
    {
        $this->abbreviation = $abbreviation;

        return $this;
    }

    public function getTestament(): ?Testament
    {
        return $this->testament;
    }

    public function setTestament(?Testament $testament): static
    {
        $this->testament = $testament;

        return $this;
    }

    public function getBookOrder(): ?int
    {
        return $this->bookOrder;
    }

    public function setBookOrder(int $bookOrder): static
    {
        $this->bookOrder = $bookOrder;

        return $this;
    }
}
