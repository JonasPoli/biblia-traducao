<?php

namespace App\Command;

use App\Repository\LouwNidaDomainRepository;
use App\Service\LouwNidaService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:louwnida:cache',
    description: 'Warms up the Louw-Nida cache for all domains and subdomains.',
)]
class LouwNidaCacheCommand extends Command
{
    public function __construct(
        private LouwNidaService $louwNidaService,
        private LouwNidaDomainRepository $louwNidaDomainRepository,
        private \Doctrine\ORM\EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Warming up Louw-Nida Cache');

        ini_set('memory_limit', '512M');

        // 1. Get all unique domain numbers
        $io->section('Fetching Domains...');
        
        // Assuming we can get all domains. 
        // If findAll() returns too many duplicates (because table has subdomains), 
        // we might need a custom query for unique domains if we iterate domains first.
        // Or we can just iterate all entries and trigger cache for both domain and subdomain.
        
        // Let's get all entries from LouwNidaDomain table
        $allEntries = $this->louwNidaDomainRepository->findAll();
        
        $domainsProcessed = [];
        $domainCount = 0;
        $subdomainCount = 0;

        $io->progressStart(count($allEntries));

        foreach ($allEntries as $entry) {
            $domainNum = $entry->getDomainNumber();
            $subdomainNum = $entry->getSubdomainNumber();

            // Cache Domain Data (only once per domain)
            if (!isset($domainsProcessed[$domainNum])) {
                if (!$this->louwNidaService->hasDomainData($domainNum)) {
                    $this->louwNidaService->getDomainData($domainNum);
                }
                $domainsProcessed[$domainNum] = true;
                $domainCount++;
            }

            // Cache Subdomain Data
            if (!$this->louwNidaService->hasSubdomainData($domainNum, $subdomainNum)) {
                $this->louwNidaService->getSubdomainData($domainNum, $subdomainNum);
                $subdomainCount++; // Only count actual generations
            }

            if (($subdomainCount % 50) === 0) {
                $this->entityManager->clear();
            }

            $io->progressAdvance();
        }

        $io->progressFinish();

        $io->success(sprintf(
            'Cache warmup complete! Processed %d domains and %d subdomains.',
            $domainCount,
            $subdomainCount
        ));

        return Command::SUCCESS;
    }
}
