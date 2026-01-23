<?php

namespace App\Command;

use App\Entity\LouwNidaDomain;
use App\Repository\LouwNidaDomainRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'app:import-louw-nida-domains',
    description: 'Imports Louw-Nida domains from CSV file',
)]
class ImportLouwNidaDomainsCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private LouwNidaDomainRepository $louwNidaDomainRepository;
    private string $projectDir;

    public function __construct(
        EntityManagerInterface $entityManager,
        LouwNidaDomainRepository $louwNidaDomainRepository,
        KernelInterface $kernel
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->louwNidaDomainRepository = $louwNidaDomainRepository;
        $this->projectDir = $kernel->getProjectDir();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $csvPath = $this->projectDir . '/docs/louw_nida_completo_pt_br_final.csv';

        if (!file_exists($csvPath)) {
            $io->error("File not found: $csvPath");
            return Command::FAILURE;
        }

        $io->title('Importing Louw-Nida Domains');

        if (($handle = fopen($csvPath, "r")) !== FALSE) {
            // Skip header
            fgetcsv($handle, 0, ",");

            $batchSize = 20;
            $i = 0;

            while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
                // "ID_LouwNida","Dominio_Principal","Exemplo_Grego","Glossario_EN","Glossario_PT_BR"
                $idString = $data[0];
                $category = $data[1];
                $greekExample = $data[2];
                $glossEnglish = $data[3];
                $glossPortuguese = $data[4];

                $parts = explode('.', $idString);
                if (count($parts) < 2) {
                    // Try to handle cases like just "1" if any
                    $domainNumber = (int) $parts[0];
                    $subdomainNumber = 0;
                } else {
                    $domainNumber = (int) $parts[0];
                    $subdomainNumber = (int) $parts[1];
                }

                // Construct semantic domain ID (e.g., 33.38)
                // The CSV has it as 1.1, so we normalize it.
                // It seems the CSV is ALREADY 1.1 format.
                $semanticDomainId = trim($idString);

                // Check if exists
                $domain = $this->louwNidaDomainRepository->findOneBy(['semanticDomainId' => $semanticDomainId]);

                if (!$domain) {
                    $domain = new LouwNidaDomain();
                    $domain->setSemanticDomainId($semanticDomainId);
                }

                $domain->setDomainNumber($domainNumber);
                $domain->setSubdomainNumber($subdomainNumber);
                $domain->setCategory($category);
                $domain->setGreekExample($greekExample);
                $domain->setGlossEnglish($glossEnglish);
                $domain->setGlossPortuguese($glossPortuguese);

                $this->entityManager->persist($domain);

                if (($i % $batchSize) === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear(); // Detach all objects from Doctrine!
                }

                $i++;
            }
            fclose($handle);

            $this->entityManager->flush(); // Flush remaining
            $this->entityManager->clear();
        }

        $io->success("Imported $i domains successfully.");

        return Command::SUCCESS;
    }
}
