<?php

namespace App\Command;

use App\Entity\Verse;
use App\Entity\VerseReference;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-references',
    description: 'Import verse references from legacy database (debugging)',
)]
class ImportReferencesCommand extends Command
{
    private Connection $legacyConnection;
    private EntityManagerInterface $entityManager;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->legacyConnection = $registry->getConnection('legacy');
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '1024M');
        $io = new SymfonyStyle($input, $output);

        $io->title('Starting Reference Import (Debug Mode)');

        // Clear existing references
        $io->section('Clearing Verse References...');
        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            $connection->executeStatement('TRUNCATE TABLE verse_reference CASCADE');
        } else {
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
            $connection->executeStatement('TRUNCATE TABLE verse_reference');
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        }
        $io->success('Verse references cleared.');

        // Import References
        $io->section('Importing References (Direct Query)...');
        
        // User provided view definition:
        // CREATE OR REPLACE view biblia_verse_reference_view as SELECT bvr.vocable, bvr.text, biblia_verse_id, bv.external_id_id from biblia_verse_reference bvr inner join biblia_verse bv on bvr.biblia_verse_id = bv.id;
        // We use the underlying query to ensure we get the ID (bvr.id) which is missing in the view definition provided.
        $sql = "SELECT bvr.id, bvr.vocable, bvr.text, bvr.biblia_verse_id, bv.external_id_id 
                FROM biblia_verse_reference bvr 
                INNER JOIN biblia_verse bv ON bvr.biblia_verse_id = bv.id";

        try {
            $rows = $this->legacyConnection->fetchAllAssociative($sql);
        } catch (\Exception $e) {
            $io->error('Failed to fetch references: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $total = count($rows);
        $io->text("Found $total references to import.");

        $importedCount = 0;
        $skippedCount = 0;
        $batchSize = 500;
        
        foreach ($rows as $i => $row) {
            $ref = null;
            
            // Try to preserve ID if it exists
            if (isset($row['id'])) {
                $ref = $this->entityManager->getRepository(VerseReference::class)->find($row['id']);
            }
            
            if (!$ref) {
                $ref = new VerseReference();
                if (isset($row['id'])) {
                    $reflection = new \ReflectionClass($ref);
                    $property = $reflection->getProperty('id');
                    $property->setAccessible(true);
                    $property->setValue($ref, $row['id']);
                }
            }

            // Check if biblia_verse_id exists in row
            if (!isset($row['biblia_verse_id'])) {
                $io->warning("Row missing biblia_verse_id: " . json_encode($row));
                $skippedCount++;
                continue;
            }

            // biblia_verse_id points to VerseText (Translation), not Verse (Canonical)
            // So we first find the VerseText, then get its Verse.
            $verseText = $this->entityManager->getRepository(\App\Entity\VerseText::class)->find($row['biblia_verse_id']);
            
            if ($verseText && $verseText->getVerse()) {
                $ref->setVerse($verseText->getVerse());
                $ref->setTerm($row['vocable'] ?? null);
                $ref->setReferenceText($row['text'] ?? null);
                $this->entityManager->persist($ref);
                $importedCount++;
            } else {
                // Only log first few failures to avoid spam
                if ($skippedCount < 10) {
                    $io->warning("Skipping reference (VerseText ID {$row['biblia_verse_id']} not found or has no Verse).");
                }
                $skippedCount++;
            }

            if (($i + 1) % $batchSize === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                $io->write('.');
            }
        }

        $this->entityManager->flush();
        $io->newLine();
        $io->success("Import completed. Imported: $importedCount. Skipped: $skippedCount.");

        return Command::SUCCESS;
    }
}
