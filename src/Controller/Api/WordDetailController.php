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
        private RmacDecoderService $rmacDecoderService
    ) {}

    #[Route('/{strongCode}', name: 'api_word_detail', methods: ['GET'])]
    public function getWordDetail(string $strongCode): Response
    {
        // 1. Fetch Strong Definition
        $definition = $this->strongDefinitionRepository->findOneBy(['code' => $strongCode]);

        // 2. Fetch Paradigm Stats
        $paradigms = $this->paradigmRepository->findBy(['strongCode' => $strongCode], ['amount' => 'DESC']);

        // 3. Process Paradigms for Chart/Table
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
            'paradigms' => $paradigmData,
        ]);
    }
}
