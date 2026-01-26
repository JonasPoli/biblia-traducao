<?php

namespace App\Service;

use App\Repository\BookRepository;
use App\Repository\LouwNidaDomainRepository;
use App\Repository\LouwNidaRepository;
use App\Repository\LouwNidaSenseRepository;
use App\Repository\VerseTextRepository;
use App\Repository\VerseWordRepository;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class LouwNidaService
{
    private const ALMEIDA_VERSION_ID = 22;

    public function __construct(
        private LouwNidaRepository $louwNidaRepository,
        private LouwNidaDomainRepository $louwNidaDomainRepository,
        private LouwNidaSenseRepository $louwNidaSenseRepository,
        private BookRepository $bookRepository,
        private VerseWordRepository $verseWordRepository,
        private VerseTextRepository $verseTextRepository,
        private CacheInterface $cache
    ) {
    }

    public function getDomainData(int $domainNumber): array
    {
        return $this->cache->get('louw_nida_domain_' . $domainNumber, function (ItemInterface $item) use ($domainNumber) {
            // Cache forever (or until manually cleared/warmed)
            // You can set expiration if needed: $item->expiresAfter(3600);
            
            $domainCategory = $this->louwNidaDomainRepository->getDomainCategory($domainNumber);
            $subdomains = $this->louwNidaDomainRepository->findSubdomainsByDomain($domainNumber);

            // Group senses by subdomain for extra info
            $subdomainSenses = [];
            foreach ($subdomains as $subdomain) {
                $lnNumber = 'LN-' . $domainNumber . '.' . $subdomain->getSubdomainNumber();
                $senses = $this->louwNidaSenseRepository->findBy(['louwNidaNumber' => $lnNumber]);
                $subdomainSenses[$subdomain->getSubdomainNumber()] = $senses; // Note: Entities will be serialized. Ensure they don't have circular refs or huge closures.
                // Ideally we should cache DTOs or Arrays, but Entities in Symfony Cache (files) usually work if detached.
                // However, Doctrine Proxies can be tricky.
                // For safety in this "Static" view, let's keep it as is, but be aware.
            }

            return [
                'domainNumber' => $domainNumber,
                'domainCategory' => $domainCategory,
                'subdomains' => $subdomains, // Doctrine Collection/Array
                'subdomainSenses' => $subdomainSenses,
            ];
        });
    }

    public function getSubdomainData(int $domainNumber, int $subdomainNumber): array
    {
        return $this->cache->get('louw_nida_subdomain_' . $domainNumber . '_' . $subdomainNumber, function (ItemInterface $item) use ($domainNumber, $subdomainNumber) {
            
            $domainCategory = $this->louwNidaDomainRepository->getDomainCategory($domainNumber);
            $subdomain = $this->louwNidaDomainRepository->findByDomainAndSubdomain($domainNumber, $subdomainNumber);

            $lnNumber = 'LN-' . $domainNumber . '.' . $subdomainNumber;
            $lnNumberShort = $domainNumber . '.' . $subdomainNumber;

            // Get senses for this subdomain
            $senses = $this->louwNidaSenseRepository->findBy(['louwNidaNumber' => $lnNumber]);
            if (empty($senses)) {
                // Try without prefix
                $senses = $this->louwNidaSenseRepository->findBy(['louwNidaNumber' => $lnNumberShort]);
            }

            // Find unique verses that use this LN number
            $uniqueVerses = $this->louwNidaRepository->findUniqueVersesByLnNumber($lnNumber);

            // Build verse data with words
            $versesData = [];
            $books = [];

            foreach ($uniqueVerses as $verseRef) {
                $book = $verseRef['book'];
                $chapter = $verseRef['chapter'];
                $verse = $verseRef['verse'];

                // Get book name if not cached
                if (!isset($books[$book])) {
                    $bookEntity = $this->bookRepository->find($book);
                    $books[$book] = $bookEntity ? $bookEntity->getName() : 'Livro ' . $book;
                }

                // Get all words in this verse
                $words = $this->louwNidaRepository->findVerseWords($book, $chapter, $verse);

                // Build translation strings and mark matching words
                $greekWords = [];
                $englishWords = [];
                $spanishWords = [];
                $portugueseWords = [];
                $matchingSenses = [];

                foreach ($words as $word) {
                    $wordLn = $word->getLnNumber();
                    $isMatch = $this->isLnMatch($wordLn, $lnNumberShort);

                    $greekWords[] = [
                        'text' => $word->getOgntA() ?? '',
                        'isMatch' => $isMatch,
                        'sn' => $word->getSn(),
                        'ln' => $wordLn,
                    ];

                    $englishWords[] = [
                        'text' => $word->getIt() ?? '',
                        'isMatch' => $isMatch,
                    ];

                    $spanishWords[] = [
                        'text' => $word->getEspanol() ?? '',
                        'isMatch' => $isMatch,
                    ];

                    // Get sense for matching words
                    if ($isMatch && $word->getSn()) {
                        $wordSense = $this->louwNidaSenseRepository->findOneBy([
                            'louwNidaNumber' => $lnNumber,
                            'strongCode' => $word->getSn(),
                        ]);
                        if (!$wordSense) {
                            $wordSense = $this->louwNidaSenseRepository->findOneBy([
                                'louwNidaNumber' => $lnNumberShort,
                                'strongCode' => $word->getSn(),
                            ]);
                        }
                        if ($wordSense && !isset($matchingSenses[$word->getSn()])) {
                            $matchingSenses[$word->getSn()] = $wordSense;
                        }
                    }
                }

                // Get Portuguese words from VerseWord table
                $verseWords = $this->verseWordRepository->findByBookChapterVerse($book, $chapter, $verse);

                // Find which positions have matching LN numbers (0-indexed)
                $matchingPositions = [];
                foreach ($words as $index => $word) {
                    $wordLn = $word->getLnNumber();
                    if ($this->isLnMatch($wordLn, $lnNumberShort)) {
                        $matchingPositions[$index] = true;
                    }
                }

                // Build Portuguese words array
                foreach ($verseWords as $index => $verseWord) {
                    $portugueseWords[] = [
                        'text' => $verseWord->getWordPortuguese() ?? '',
                        'isMatch' => isset($matchingPositions[$index]),
                    ];
                }

                // Get Almeida text from VerseText (version_id = 22)
                $almeidaText = '';
                if (!empty($verseWords)) {
                    $verseEntity = $verseWords[0]->getVerse();
                    if ($verseEntity) {
                        $verseText = $this->verseTextRepository->findOneBy([
                            'verse' => $verseEntity,
                            'version' => self::ALMEIDA_VERSION_ID,
                        ]);
                        if ($verseText) {
                            $rawText = $verseText->getText();

                            // Remove <pb/> tags (page breaks)
                            $almeidaText = preg_replace('/<pb\s*\/?>/i', '', $rawText);

                            // Remove <n>Hebrew/Greek words</n> tags WITH their content
                            $almeidaText = preg_replace('/<n>[^<]*<\/n>/is', '', $almeidaText);

                            // Remove <S>Strong codes</S> tags WITH their content
                            $almeidaText = preg_replace('/<S>[^<]*<\/S>/is', '', $almeidaText);

                            // Extract text from span tags (keep text, remove tag attributes)
                            $almeidaText = preg_replace('/<span[^>]*>(.*?)<\/span>/is', '$1', $almeidaText);

                            // Strip any remaining HTML tags
                            $almeidaText = strip_tags($almeidaText);

                            // Remove any remaining Strong codes (H/G followed by numbers)
                            $almeidaText = preg_replace('/[HG]\d+/', '', $almeidaText);

                            // Remove extra whitespace
                            $almeidaText = preg_replace('/\s+/', ' ', $almeidaText);
                            $almeidaText = trim($almeidaText);
                        }
                    }
                }

                $versesData[] = [
                    'book' => $book,
                    'bookName' => $books[$book],
                    'chapter' => $chapter,
                    'verse' => $verse,
                    'greekWords' => $greekWords,
                    'englishWords' => $englishWords,
                    'spanishWords' => $spanishWords,
                    'portugueseWords' => $portugueseWords,
                    'almeidaText' => $almeidaText,
                    'matchingSenses' => $matchingSenses,
                ];
            }

            return [
                'domainNumber' => $domainNumber,
                'subdomainNumber' => $subdomainNumber,
                'domainCategory' => $domainCategory,
                'subdomain' => $subdomain,
                'senses' => $senses,
                'versesData' => $versesData,
                'lnNumber' => $lnNumber,
            ];
        });
    }

    /**
     * Check if a word's LN number matches the target (handles multiple comma-separated values)
     * Compares domain and subdomain as integers to handle cases like 10.3 vs 10.30
     */
    private function isLnMatch(?string $wordLn, string $targetLn): bool
    {
        if (!$wordLn) {
            return false;
        }

        // Parse target LN number
        $targetParts = explode('.', str_replace('LN-', '', $targetLn));
        $targetDomain = (int) ($targetParts[0] ?? 0);
        $targetSubdomain = (int) ($targetParts[1] ?? 0);

        // Split by comma (with unicode comma support)
        $lnParts = preg_split('/[，,]/u', $wordLn);

        foreach ($lnParts as $part) {
            $part = trim(str_replace('LN-', '', $part));
            $partPieces = explode('.', $part);
            $partDomain = (int) ($partPieces[0] ?? 0);
            $partSubdomain = (int) ($partPieces[1] ?? 0);

            if ($partDomain === $targetDomain && $partSubdomain === $targetSubdomain) {
                return true;
            }
        }

        return false;
    }
}
