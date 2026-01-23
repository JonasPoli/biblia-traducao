<?php

namespace App\Command;

use App\Entity\LouwNida;
use App\Repository\LouwNidaDomainRepository;
use App\Repository\LouwNidaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-louw-nida-links',
    description: 'Updates LouwNida domain links with specific 10.30 -> 10.3 logic',
)]
class UpdateLouwNidaLinksCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private LouwNidaRepository $louwNidaRepository;
    private LouwNidaDomainRepository $louwNidaDomainRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        LouwNidaRepository $louwNidaRepository,
        LouwNidaDomainRepository $louwNidaDomainRepository
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->louwNidaRepository = $louwNidaRepository;
        $this->louwNidaDomainRepository = $louwNidaDomainRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Updating Louw-Nida Domain Links');

        // Allow more memory
        ini_set('memory_limit', '1G');

        // Iterate
        $batchSize = 1000;

        $query = $this->entityManager->createQuery('SELECT l FROM App\Entity\LouwNida l');
        $iterableResult = $query->toIterable();

        $progressBar = new ProgressBar($output);

        $i = 0;
        $updated = 0;

        // Pre-fetch domains to avoid N+1 queries?
        // There are ~7000 domains. We can cache them in memory.
        // Pre-fetch domains map (Semantic ID -> Database ID)
        $allDomains = $this->louwNidaDomainRepository->findAll();
        $domainIdMap = [];
        foreach ($allDomains as $d) {
            $domainIdMap[$d->getSemanticDomainId()] = $d->getId();
        }
        // Clear to free memory, we only need IDs
        $this->entityManager->clear();

        // Helper to find domain ID
        $findDomainId = function ($dNum, $subNum) use ($domainIdMap) {
            $idStr = "$dNum.$subNum";
            return $domainIdMap[$idStr] ?? null;
        };

        foreach ($iterableResult as $louwNida) {
            $lnRaw = $louwNida->getLnNumber();
            if (!$lnRaw)
                continue;

            // Extract first domain ID: LN-33.38 or LN-10.24，33.19
            // Remove LN-
            if (!str_starts_with($lnRaw, 'LN-'))
                continue;

            $clean = substr($lnRaw, 3);
            // Split by comma (fullwidth or normal)
            $domains = preg_split('/[，,]/u', $clean);
            $firstDomainCode = trim($domains[0] ?? '');

            if (!$firstDomainCode)
                continue;

            // Parse X.Y
            $parts = explode('.', $firstDomainCode);
            if (count($parts) < 2)
                continue;

            $dNum = (int) $parts[0];
            $subRaw = $parts[1]; // Keep string initially to check for trailing zero
            $sNum = (int) $subRaw;

            // Strategy 1: Exact Match
            $targetId = $findDomainId($dNum, $sNum);

            // Strategy 2: User rule "10.30 -> 10.3"
            if (!$targetId && str_ends_with($subRaw, '0')) {
                // Try removing ONE trailing zero? Or all? 
                // "terminado em zero, o zero pode ser desconsiderado" -> implies single zero suffix?
                // Let's try dividing by 10 if divisible.
                if ($sNum > 0 && ($sNum % 10 === 0)) {
                    $sNumNew = $sNum / 10;
                    $targetId = $findDomainId($dNum, $sNumNew);
                }
            }

            if ($targetId) {
                // Get proxy reference
                $domainRef = $this->entityManager->getReference('App\Entity\LouwNidaDomain', $targetId);
                $louwNida->setLouwNidaDomain($domainRef);
                $updated++;
            }

            if (($i % $batchSize) === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                // Re-load cache if needed? No, domain entities are detached but we stored them in $domainMap.
            }
            $progressBar->advance();
            $i++;
        }

        $this->entityManager->flush();
        $progressBar->finish();

        $io->success("Updated $updated links.");

        return Command::SUCCESS;
    }
}
