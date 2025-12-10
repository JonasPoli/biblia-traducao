<?php

namespace App\Controller\Api;

use App\Repository\ParadigmRepository;
use App\Repository\StrongDefinitionRepository;
use App\Service\RmacDecoderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/word-detail')]
class WordDetailController extends AbstractController
{
    public function __construct(
        private StrongDefinitionRepository $strongDefinitionRepository,
        private ParadigmRepository $paradigmRepository,
        private RmacDecoderService $rmacDecoderService,
        private \App\Service\StrongFormatter $strongFormatter
    ) {
    }

    #[Route('/{strongCode}', name: 'api_word_detail', methods: ['GET'])]
    public function getWordDetail(string $strongCode, \Symfony\Component\HttpFoundation\Request $request): Response
    {
        // 1. Fetch Strong Definition
        $definition = $this->strongDefinitionRepository->findOneBy(['code' => $strongCode]);

        // Get term and pt_type from query param
        $term = $request->query->get('term');
        $ptType = $request->query->get('pt_type');

        // Build Custom Reference Content
        // Format: "Portuguese Type, Term; Extracted Definition"
        $referenceContent = '';
        if ($definition) {
            $defText = $definition->getDefinition();

            // New Strategy: Just remove the <p class='header'>...</p> noise
            // User request: "Apenas retire o <p class='header'></p>"
            $extractedDef = preg_replace('/<p\s+class=[\'"]header[\'"]>.*?<\/p>/s', '', $defText);

            // Trim extra whitespace
            $extractedDef = trim($extractedDef);

            // Apply StrongFormatter to the extracted definition
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



        // 2. Fetch Paradigm Stats
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

        return $this->render('translation/_word_detail.html.twig', [
            'strongCode' => $strongCode,
            'definition' => $definition,
            'term' => $term,
            'referenceContent' => $referenceContent,
            'formattedDefinition' => $definition ? $this->strongFormatter->transform($definition->getDefinition() ?? '') : null,
            'formattedFullDefinition' => $definition ? $this->strongFormatter->formatFullDefinition($definition->getFullDefinition()) : null,
            'paradigms' => $paradigmData,
        ]);
    }
}
