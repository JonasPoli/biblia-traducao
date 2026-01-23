<?php

namespace App\Entity;

use App\Repository\LouwNidaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LouwNidaRepository::class)]
class LouwNida
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $ogntSort = null;

    #[ORM\Column(nullable: true)]
    private ?int $tanttSort = null;

    #[ORM\Column(nullable: true)]
    private ?int $featuresSort = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $levinsohnClauseId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $otQuotation = null;

    #[ORM\Column(nullable: true)]
    private ?int $bgbSort = null;

    #[ORM\Column(nullable: true)]
    private ?int $ltSort = null;

    #[ORM\Column(nullable: true)]
    private ?int $stSort = null;

    #[ORM\Column]
    private ?int $book = null;

    #[ORM\Column]
    private ?int $chapter = null;

    #[ORM\Column]
    private ?int $verse = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ogntK = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $ogntU = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ogntA = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lexeme = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $rmac = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sn = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bdagEntry = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $edntEntry = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mounceEntry = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gkNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lnNumber = null;

    #[ORM\ManyToOne]
    private ?LouwNidaDomain $louwNidaDomain = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $transSblCap = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $transSbl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $modernGreek = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phonetic = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $tbesg = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $it = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $lt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $st = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $espanol = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pmpWord = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pmfWord = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOgntSort(): ?int
    {
        return $this->ogntSort;
    }

    public function setOgntSort(int $ogntSort): static
    {
        $this->ogntSort = $ogntSort;

        return $this;
    }

    public function getTanttSort(): ?int
    {
        return $this->tanttSort;
    }

    public function setTanttSort(?int $tanttSort): static
    {
        $this->tanttSort = $tanttSort;

        return $this;
    }

    public function getFeaturesSort(): ?int
    {
        return $this->featuresSort;
    }

    public function setFeaturesSort(?int $featuresSort): static
    {
        $this->featuresSort = $featuresSort;

        return $this;
    }

    public function getLevinsohnClauseId(): ?string
    {
        return $this->levinsohnClauseId;
    }

    public function setLevinsohnClauseId(?string $levinsohnClauseId): static
    {
        $this->levinsohnClauseId = $levinsohnClauseId;

        return $this;
    }

    public function getOtQuotation(): ?string
    {
        return $this->otQuotation;
    }

    public function setOtQuotation(?string $otQuotation): static
    {
        $this->otQuotation = $otQuotation;

        return $this;
    }

    public function getBgbSort(): ?int
    {
        return $this->bgbSort;
    }

    public function setBgbSort(?int $bgbSort): static
    {
        $this->bgbSort = $bgbSort;

        return $this;
    }

    public function getLtSort(): ?int
    {
        return $this->ltSort;
    }

    public function setLtSort(?int $ltSort): static
    {
        $this->ltSort = $ltSort;

        return $this;
    }

    public function getStSort(): ?int
    {
        return $this->stSort;
    }

    public function setStSort(?int $stSort): static
    {
        $this->stSort = $stSort;

        return $this;
    }

    public function getBook(): ?int
    {
        return $this->book;
    }

    public function setBook(int $book): static
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

    public function getOgntK(): ?string
    {
        return $this->ogntK;
    }

    public function setOgntK(?string $ogntK): static
    {
        $this->ogntK = $ogntK;

        return $this;
    }

    public function getOgntU(): ?string
    {
        return $this->ogntU;
    }

    public function setOgntU(?string $ogntU): static
    {
        $this->ogntU = $ogntU;

        return $this;
    }

    public function getOgntA(): ?string
    {
        return $this->ogntA;
    }

    public function setOgntA(?string $ogntA): static
    {
        $this->ogntA = $ogntA;

        return $this;
    }

    public function getLexeme(): ?string
    {
        return $this->lexeme;
    }

    public function setLexeme(?string $lexeme): static
    {
        $this->lexeme = $lexeme;

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

    public function getSn(): ?string
    {
        return $this->sn;
    }

    public function setSn(?string $sn): static
    {
        $this->sn = $sn;

        return $this;
    }

    public function getBdagEntry(): ?string
    {
        return $this->bdagEntry;
    }

    public function setBdagEntry(?string $bdagEntry): static
    {
        $this->bdagEntry = $bdagEntry;

        return $this;
    }

    public function getEdntEntry(): ?string
    {
        return $this->edntEntry;
    }

    public function setEdntEntry(?string $edntEntry): static
    {
        $this->edntEntry = $edntEntry;

        return $this;
    }

    public function getMounceEntry(): ?string
    {
        return $this->mounceEntry;
    }

    public function setMounceEntry(?string $mounceEntry): static
    {
        $this->mounceEntry = $mounceEntry;

        return $this;
    }

    public function getGkNumber(): ?string
    {
        return $this->gkNumber;
    }

    public function setGkNumber(?string $gkNumber): static
    {
        $this->gkNumber = $gkNumber;

        return $this;
    }

    public function getLnNumber(): ?string
    {
        return $this->lnNumber;
    }

    public function setLnNumber(?string $lnNumber): static
    {
        $this->lnNumber = $lnNumber;

        return $this;
    }

    public function getLouwNidaDomain(): ?LouwNidaDomain
    {
        return $this->louwNidaDomain;
    }

    public function setLouwNidaDomain(?LouwNidaDomain $louwNidaDomain): static
    {
        $this->louwNidaDomain = $louwNidaDomain;

        return $this;
    }

    public function getTransSblCap(): ?string
    {
        return $this->transSblCap;
    }

    public function setTransSblCap(?string $transSblCap): static
    {
        $this->transSblCap = $transSblCap;

        return $this;
    }

    public function getTransSbl(): ?string
    {
        return $this->transSbl;
    }

    public function setTransSbl(?string $transSbl): static
    {
        $this->transSbl = $transSbl;

        return $this;
    }

    public function getModernGreek(): ?string
    {
        return $this->modernGreek;
    }

    public function setModernGreek(?string $modernGreek): static
    {
        $this->modernGreek = $modernGreek;

        return $this;
    }

    public function getPhonetic(): ?string
    {
        return $this->phonetic;
    }

    public function setPhonetic(?string $phonetic): static
    {
        $this->phonetic = $phonetic;

        return $this;
    }

    public function getTbesg(): ?string
    {
        return $this->tbesg;
    }

    public function setTbesg(?string $tbesg): static
    {
        $this->tbesg = $tbesg;

        return $this;
    }

    public function getIt(): ?string
    {
        return $this->it;
    }

    public function setIt(?string $it): static
    {
        $this->it = $it;

        return $this;
    }

    public function getLt(): ?string
    {
        return $this->lt;
    }

    public function setLt(?string $lt): static
    {
        $this->lt = $lt;

        return $this;
    }

    public function getSt(): ?string
    {
        return $this->st;
    }

    public function setSt(?string $st): static
    {
        $this->st = $st;

        return $this;
    }

    public function getEspanol(): ?string
    {
        return $this->espanol;
    }

    public function setEspanol(?string $espanol): static
    {
        $this->espanol = $espanol;

        return $this;
    }

    public function getPmpWord(): ?string
    {
        return $this->pmpWord;
    }

    public function setPmpWord(?string $pmpWord): static
    {
        $this->pmpWord = $pmpWord;

        return $this;
    }

    public function getPmfWord(): ?string
    {
        return $this->pmfWord;
    }

    public function setPmfWord(?string $pmfWord): static
    {
        $this->pmfWord = $pmfWord;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }
}
