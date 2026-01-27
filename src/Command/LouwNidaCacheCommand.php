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

        ini_set('memory_limit', '-1');

        // 1. Get all unique domain numbers
        $io->section('Fetching Domains...');
        
        // Count for progress bar
        $totalEntries = $this->louwNidaDomainRepository->count([]);
        
        // Use iterable to avoid loading all objects into memory at once
        $query = $this->louwNidaDomainRepository->createQueryBuilder('d')->getQuery();
        
        $domainsProcessed = [];
        $domainCount = 0;
        $subdomainCount = 0;

        $io->progressStart($totalEntries);

        foreach ($query->toIterable() as $entry) {
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
