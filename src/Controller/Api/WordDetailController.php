<?php

namespace App\Controller\Api;

use App\Repository\StrongDefinitionRepository;
use App\Repository\ParadigmRepository;
use App\Service\RmacDecoderService;
use App\Repository\LouwNidaRepository;
use App\Repository\LouwNidaSenseRepository;
use App\Repository\LouwNidaDomainRepository;
use App\Service\StrongFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/word-detail')]
class WordDetailController extends AbstractController
{
    public function __construct(
        private StrongDefinitionRepository $strongDefinitionRepository,
        private ParadigmRepository $paradigmRepository,
        private RmacDecoderService $rmacDecoderService,
        private LouwNidaRepository $louwNidaRepository,
        private StrongFormatter $strongFormatter,
        private LouwNidaSenseRepository $louwNidaSenseRepository,
        private LouwNidaDomainRepository $louwNidaDomainRepository
    ) {
    }

    #[Route('/{strongCode}', name: 'api_word_detail', methods: ['GET'])]
    public function getWordDetail(string $strongCode, Request $request): Response
    {
        // 1. Strong definition
        $definition = $this->strongDefinitionRepository->findOneBy(['code' => $strongCode]);
        $term = $request->query->get('term');
        $ptType = $request->query->get('pt_type');

        // Build reference content
        $referenceContent = '';
        if ($definition) {
            $defText = $definition->getDefinition();
            $extractedDef = preg_replace('/<p\s+class=[\'\"]header[\'\"]>.*?<\/p>/s', '', $defText);
            $extractedDef = trim($extractedDef);
            $extractedDef = $this->strongFormatter->transform($extractedDef);
            $parts = [];
            if ($ptType)
                $parts[] = $ptType;
            if ($term)
                $parts[] = $term;
            $prefix = implode(', ', $parts);
            if ($prefix && $extractedDef) {
                $referenceContent = "{$prefix}; {$extractedDef}";
            } elseif ($extractedDef) {
                $referenceContent = $extractedDef;
            } elseif ($prefix) {
                $referenceContent = $prefix;
            }
        }

        // 2. Paradigm stats
        $paradigms = $this->paradigmRepository->findBy(['strongCode' => $strongCode], ['amount' => 'DESC']);
        $paradigmData = [];
        foreach ($paradigms as $p) {
            $rmacCode = $p->getRmac();
            $rmacDescription = $rmacCode ? $this->rmacDecoderService->decode($rmacCode) : 'N/A';
            $paradigmData[] = [
                'translation' => $p->getTranslation(),
                'rmac' => $rmacCode,
                'rmacDescription' => $rmacDescription,
                'amount' => $p->getAmount(),
                'wordClass' => $p->getWordClass(),
            ];
        }

        // 3. Contextual Louw‑Nida domain (specific verse)
        $bookId = $request->query->get('book');
        $chapter = $request->query->get('chapter');
        $verseNum = $request->query->get('verse');
        $sort = $request->query->get('sort');
        $contextualDomain = null;
        if ($bookId && $chapter && $verseNum && $sort) {
            $specificLn = $this->louwNidaRepository->findOneBy([
                'book' => $bookId,
                'chapter' => $chapter,
                'verse' => $verseNum,
                'ogntSort' => $sort,
            ]);
            if ($specificLn && $specificLn->getLouwNidaDomain()) {
                $contextualDomain = $specificLn->getLouwNidaDomain();
            }
        }

        // 4. Global Louw‑Nida domains for this Strong code
        $lnOccurrences = $this->louwNidaRepository->findBy(['sn' => $strongCode]);
        $louwNidaDomains = [];
        $seenDomainIds = [];
        foreach ($lnOccurrences as $ln) {
            $domain = $ln->getLouwNidaDomain();
            if ($domain && !isset($seenDomainIds[$domain->getId()])) {
                $louwNidaDomains[] = $domain;
                $seenDomainIds[$domain->getId()] = true;
            }
        }
        usort($louwNidaDomains, fn($a, $b) => $a->getDomainNumber() <=> $b->getDomainNumber() ?: $a->getSubdomainNumber() <=> $b->getSubdomainNumber());

        // 5. Gather Louw‑Nida info (sense + domain) for display - deduplicated
        $louwNidaInfos = [];
        $seenInfoKeys = [];
        foreach ($lnOccurrences as $ln) {
            $rawLnNumber = $ln->getLnNumber();
            if (!$rawLnNumber)
                continue;
            $parts = preg_split('/[，,]/u', $rawLnNumber);
            foreach ($parts as $part) {
                $part = trim($part);
                if (empty($part))
                    continue;
                $lnNumber = str_starts_with($part, 'LN-') ? $part : 'LN-' . $part;

                // Create unique key to avoid duplicates
                $uniqueKey = $lnNumber . '|' . $strongCode;
                if (isset($seenInfoKeys[$uniqueKey])) {
                    continue;
                }
                $seenInfoKeys[$uniqueKey] = true;

                $sense = $this->louwNidaSenseRepository->findOneBy([
                    'louwNidaNumber' => $lnNumber,
                    'strongCode' => $strongCode,
                ]);

                // Try to get domain from relation first
                $domain = $ln->getLouwNidaDomain();

                // If no relation, lookup by domainNumber and subdomainNumber
                // Parse "LN-93.169" into domainNumber=93 and subdomainNumber=169
                if (!$domain) {
                    $semanticId = str_replace('LN-', '', $lnNumber);
                    $domainParts = explode('.', $semanticId);
                    if (count($domainParts) >= 2) {
                        $domainNumber = (int) $domainParts[0];
                        $subdomainNumber = (int) $domainParts[1];
                        $domain = $this->louwNidaDomainRepository->findOneBy([
                            'domainNumber' => $domainNumber,
                            'subdomainNumber' => $subdomainNumber,
                        ]);
                    }
                }

                $info = ['lnNumber' => $lnNumber];
                if ($domain) {
                    $info['category'] = $domain->getCategory();
                    $info['glossEnglish'] = $domain->getGlossEnglish();
                    $info['glossPortuguese'] = $domain->getGlossPortuguese();
                }
                if ($sense) {
                    $info['ideiaCentralPtBr'] = $sense->getIdeiaCentralPtBr();
                    $info['sentidoSemanticoPtBr'] = $sense->getSentidoSemanticoPtBr();
                }
                $louwNidaInfos[] = $info;
            }
        }

        return $this->render('translation/_word_detail.html.twig', [
            'strongCode' => $strongCode,
            'definition' => $definition,
            'term' => $term,
            'referenceContent' => $referenceContent,
            'formattedDefinition' => $definition ? $this->strongFormatter->transform($definition->getDefinition() ?? '') : null,
            'formattedFullDefinition' => $definition ? $this->strongFormatter->formatFullDefinition($definition->getFullDefinition()) : null,
            'paradigms' => $paradigmData,
            'louwNidaDomains' => $louwNidaDomains,
            'contextualDomain' => $contextualDomain,
            'louwNidaInfos' => $louwNidaInfos,
        ]);
    }
}
