<?php

namespace App\Entity;

use App\Repository\VerseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VerseRepository::class)]
class Verse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Book $book = null;

    #[ORM\Column]
    private ?int $chapter = null;

    #[ORM\Column]
    private ?int $verse = null;

    /**
     * @var Collection<int, VerseText>
     */
    #[ORM\OneToMany(targetEntity: VerseText::class, mappedBy: 'verse')]
    private Collection $verseTexts;

    public function __construct()
    {
        $this->verseTexts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBook(): ?Book
    {
        return $this->book;
    }

    public function setBook(?Book $book): static
    {
        $this->book = $book;

        return $this;
    }

    public function getChapter(): ?int
    {
        return $this->chapter;
    }

    public function setChapter(int $chapter): static
    {
        $this->chapter = $chapter;

        return $this;
    }

    public function getVerse(): ?int
    {
        return $this->verse;
    }

    public function setVerse(int $verse): static
    {
        $this->verse = $verse;

        return $this;
    }

    /**
     * @return Collection<int, VerseText>
     */
    public function getVerseTexts(): Collection
    {
        return $this->verseTexts;
    }
}
