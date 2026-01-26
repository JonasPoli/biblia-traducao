<?php

namespace App\Controller\Admin;

use App\Repository\LouwNidaDomainRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LouwNidaController extends AbstractController
{
    private const ALMEIDA_VERSION_ID = 22;

    public function __construct(
        private \App\Service\LouwNidaService $louwNidaService,
        private LouwNidaDomainRepository $louwNidaDomainRepository // Still needed for getDomainCategory in index() if we keep logic there, or move index logic too?
        // Actually index() just parses params and calls showDomain/showSubdomain.
        // But getDomainCategory is used in index() before calling them.
        // Let's keep logic in controller minimal.
    ) {
    }

    #[Route('/admin/louwnida/{word}', name: 'admin_louwnida', requirements: ['word' => '.+'])]
    public function index(string $word): Response
    {
        // Parse the word parameter (can be "LN-93" or "LN-93.387" or just "93" or "93.387")
        $normalized = str_replace('LN-', '', $word);
        $parts = explode('.', $normalized);

        $domainNumber = (int) $parts[0];
        $subdomainNumber = isset($parts[1]) ? (int) $parts[1] : null;

        // Get domain category
        $domainCategory = $this->louwNidaDomainRepository->getDomainCategory($domainNumber);

        if ($subdomainNumber === null) {
            // Show domain view with all subdomains
            return $this->showDomain($domainNumber, $domainCategory);
        }

        // Show subdomain view with occurrences
        return $this->showSubdomain($domainNumber, $subdomainNumber, $domainCategory);
    }

    private function showDomain(int $domainNumber, ?string $domainCategory): Response
    {
        $data = $this->louwNidaService->getDomainData($domainNumber);

        return $this->render('admin/louwnida/domain.html.twig', $data);
    }

    private function showSubdomain(int $domainNumber, int $subdomainNumber, ?string $domainCategory): Response
    {
        $data = $this->louwNidaService->getSubdomainData($domainNumber, $subdomainNumber);

        return $this->render('admin/louwnida/subdomain.html.twig', $data);
    }

    // isLnMatch moved to LouwNidaService
}
