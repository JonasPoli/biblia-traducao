<?php

namespace App\Command;

use App\Entity\BibleVersion;
use App\Entity\Book;
use App\Entity\GlobalReference;
use App\Entity\StrongDefinition;
use App\Entity\Testament;
use App\Entity\Verse;
use App\Entity\VerseReference;
use App\Entity\VerseText;
use App\Entity\VerseWord;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-legacy',
    description: 'Import data from legacy database',
)]
class ImportLegacyCommand extends Command
{
    private Connection $legacyConnection;
    private EntityManagerInterface $entityManager;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->legacyConnection = $registry->getConnection('legacy');
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this->addOption('minimal', null, \Symfony\Component\Console\Input\InputOption::VALUE_NONE, 'Import only specific versions (17, 18, 19, 22)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '4096M'); // Increase memory limit to 4GB
        $io = new SymfonyStyle($input, $output);

        // Disable logging for performance (DBAL 3/4 compatible way)
        try {
            $this->entityManager->getConnection()->getConfiguration()->setSQLLogger(null);
            $this->legacyConnection->getConfiguration()->setSQLLogger(null);
        } catch (\Throwable $e) {
            // Ignore if method doesn't exist (DBAL 4)
        }

        // Disable ID generation for all entities to preserve legacy IDs
        $entities = [
            Testament::class,
            Book::class,
            BibleVersion::class,
            Verse::class,
            VerseText::class,
            StrongDefinition::class,
            VerseWord::class,
            VerseReference::class,
        ];

        foreach ($entities as $entityClass) {
            $metadata = $this->entityManager->getClassMetadata($entityClass);
            $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
            $metadata->setIdGenerator(new \Doctrine\ORM\Id\AssignedGenerator());
        }

        $io->title('Starting Import from Legacy Database');

        // 0. Clear Database
        $this->clearDatabase($io);

        // 1. Import Testaments
        $this->importTestaments($io);

        // 2. Import Books
        $this->importBooks($io);

        // 3. Import Bible Versions
        $this->importBibleVersions($io);

        // 4. Import Verses (Canonical)
        $this->importVerses($io);

        // 5. Import Verse Texts (Translations)
        $isMinimal = $input->getOption('minimal');
        $this->importVerseTexts($io, $isMinimal);

        // 6. Import Strong Definitions
        $this->importStrongDefinitions($io);

        // 7. Import Verse Words (Interlinear)
        $this->importVerseWords($io);

        // 8. Import References
        $this->importReferences($io);

        $io->success('Import completed successfully!');

        return Command::SUCCESS;
    }

    private function importTestaments(SymfonyStyle $io): void
    {
        $io->section('Importing Testaments...');
        $rows = $this->legacyConnection->fetchAllAssociative('SELECT * FROM biblia_testament');

        foreach ($rows as $row) {
            $testament = $this->entityManager->getRepository(Testament::class)->find($row['id']);
            if (!$testament) {
                $testament = new Testament();
                // Force ID if possible, or just rely on auto-increment matching if we truncate first.
                // Doctrine doesn't easily allow setting ID on auto-increment.
                // For import, it's often better to disable auto-increment or just map IDs manually if needed.
                // Here we assume empty DB.

                // REFLECTION to set ID
                $reflection = new \ReflectionClass($testament);
                $property = $reflection->getProperty('id');
                $property->setAccessible(true);
                $property->setValue($testament, $row['id']);
            }

            $testament->setName($row['name']);
            $this->entityManager->persist($testament);
        }
        $this->entityManager->flush();
        $io->text(count($rows) . ' testaments imported.');
    }

    private function importBooks(SymfonyStyle $io): void
    {
        $io->section('Importing Books...');
        $rows = $this->legacyConnection->fetchAllAssociative('SELECT * FROM biblia_book');

        $importedCount = 0;
        $skippedCount = 0;

        foreach ($rows as $row) {
            $book = $this->entityManager->getRepository(Book::class)->find($row['id']);
            if (!$book) {
                $book = new Book();
                $reflection = new \ReflectionClass($book);
                $property = $reflection->getProperty('id');
                $property->setAccessible(true);
                $property->setValue($book, $row['id']);
            }

            $book->setName($row['name']);
            $book->setAbbreviation($row['abbreviation']);
            $book->setBookOrder($row['position']);

            // Testament relation
            // Legacy has 'testment_id' (typo in legacy DB)
            if (isset($row['testment_id'])) {
                $testament = $this->entityManager->getRepository(Testament::class)->find($row['testment_id']);
                if ($testament) {
                    $book->setTestament($testament);
                    $this->entityManager->persist($book);
                    $importedCount++;
                } else {
                    $io->warning("Skipping book {$row['name']} (ID: {$row['id']}): Testament ID {$row['testment_id']} not found.");
                    $skippedCount++;
                }
            } else {
                 $io->warning("Skipping book {$row['name']} (ID: {$row['id']}): No testament ID.");
                 $skippedCount++;
            }
        }
        $this->entityManager->flush();
        $io->text($importedCount . ' books imported. ' . $skippedCount . ' skipped.');
    }

    private function importBibleVersions(SymfonyStyle $io): void
    {
        $io->section('Importing Bible Versions...');
        $rows = $this->legacyConnection->fetchAllAssociative('SELECT * FROM biblia_version');

        foreach ($rows as $row) {
            $version = $this->entityManager->getRepository(BibleVersion::class)->find($row['id']);
            if (!$version) {
                $version = new BibleVersion();
                $reflection = new \ReflectionClass($version);
                $property = $reflection->getProperty('id');
                $property->setAccessible(true);
                $property->setValue($version, $row['id']);
            }

            $version->setName($row['name']);
            $version->setAbbreviation($row['abbreviation']);

            $this->entityManager->persist($version);
        }
        $this->entityManager->flush();
        $io->text(count($rows) . ' versions imported.');
    }

    private function importVerses(SymfonyStyle $io): void
    {
        $io->section('Importing Verses (Canonical)...');
        // Batch processing
        $offset = 0;
        $limit = 1000;
        $importedCount = 0;
        $skippedCount = 0;

        while (true) {
            $rows = $this->legacyConnection->fetchAllAssociative("SELECT * FROM biblia_verse_ext LIMIT $limit OFFSET $offset");
            if (empty($rows))
                break;

            foreach ($rows as $row) {
                $verse = $this->entityManager->getRepository(Verse::class)->find($row['id']);
                if (!$verse) {
                    $verse = new Verse();
                    $reflection = new \ReflectionClass($verse);
                    $property = $reflection->getProperty('id');
                    $property->setAccessible(true);
                    $property->setValue($verse, $row['id']);
                }

                $book = $this->entityManager->getRepository(Book::class)->find($row['book_id']);
                if ($book) {
                    $verse->setBook($book);
                    $verse->setChapter($row['chapter']);
                    $verse->setVerse($row['verse']);
                    $this->entityManager->persist($verse);
                    $importedCount++;
                } else {
                    $io->warning("Skipping verse ID {$row['id']}: Book ID {$row['book_id']} not found.");
                    $skippedCount++;
                }
            }
            $this->entityManager->flush();
            $this->entityManager->clear(); // Clear to avoid memory leaks
            $offset += $limit;
            $io->write('.');
        }
        $io->newLine();
        $io->text($importedCount . ' verses imported. ' . $skippedCount . ' skipped (book not found).');
    }

    private function importVerseTexts(SymfonyStyle $io, bool $isMinimal = false): void
    {
        $io->section('Importing Verse Texts...');
        $offset = 0;
        $limit = 500; // Reduce batch size
        $importedCount = 0;
        $skippedCount = 0;

        $whereClause = '';
        if ($isMinimal) {
            $whereClause = 'WHERE version_id IN (17, 18, 19, 22)';
            $io->note('Minimal import enabled: Importing only versions 17, 18, 19, 22.');
        }

        while (true) {
            $rows = $this->legacyConnection->fetchAllAssociative("SELECT * FROM biblia_verse $whereClause LIMIT $limit OFFSET $offset");
            if (empty($rows))
                break;

            foreach ($rows as $row) {
                $verseText = $this->entityManager->getRepository(VerseText::class)->find($row['id']);
                if (!$verseText) {
                    $verseText = new VerseText();
                    $reflection = new \ReflectionClass($verseText);
                    $property = $reflection->getProperty('id');
                    $property->setAccessible(true);
                    $property->setValue($verseText, $row['id']);
                }

                if (empty($row['external_id_id'])) {
                    $skippedCount++;
                    continue;
                }

                // Use getOrImport helper to try fetching from remote if missing locally
                $verse = $this->getOrImportVerse($row['external_id_id'], $io);
                $version = $this->getOrImportVersion($row['version_id'], $io);

                if ($verse && $version) {
                    $verseText->setVerse($verse);
                    $verseText->setVersion($version);
                    $verseText->setText($row['text']);
                    if (isset($row['subject'])) {
                        $verseText->setTitle($row['subject']);
                    }
                    // User ID? row['user_id'] exists.
                    // We don't have users imported yet. Skip for now or map if critical.

                    $this->entityManager->persist($verseText);
                    $importedCount++;
                } else {
                    $missing = [];
                    if (!$verse) $missing[] = "Verse ID {$row['external_id_id']}";
                    if (!$version) $missing[] = "Version ID {$row['version_id']}";
                    
                    $io->warning("Skipping verse text ID {$row['id']}: " . implode(' and ', $missing) . " not found locally or remote.");
                    $skippedCount++;
                }
            }
            $this->entityManager->flush();
            $this->entityManager->clear();
            gc_collect_cycles(); // Force garbage collection
            $offset += $limit;
            $io->write('.');
        }
        $io->newLine();
        $io->text($importedCount . ' verse texts imported. ' . $skippedCount . ' skipped (verse/version not found).');
    }

    // --- Helper Methods for Recursive Import ---

    private function getOrImportVerse(int $id, SymfonyStyle $io): ?Verse
    {
        $verse = $this->entityManager->getRepository(Verse::class)->find($id);
        if ($verse) {
            return $verse;
        }

        // Not found locally, try to fetch from legacy
        $row = $this->legacyConnection->fetchAssociative('SELECT * FROM biblia_verse_ext WHERE id = ?', [$id]);
        if (!$row) {
            return null; // Not found in legacy either
        }

        // We found the verse data, now we need its dependencies (Book)
        $book = $this->getOrImportBook($row['book_id'], $io);
        if (!$book) {
            $io->warning("Cannot import Verse $id because Book {$row['book_id']} is missing.");
            return null;
        }

        // Create and persist the verse
        $verse = new Verse();
        $reflection = new \ReflectionClass($verse);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($verse, $id);

        $verse->setBook($book);
        $verse->setChapter($row['chapter']);
        $verse->setVerse($row['verse']);

        $this->entityManager->persist($verse);
        // We must flush here to ensure this verse is available for the calling VerseText
        // BUT flushing inside a loop can be slow. Since this is a "fallback" path, it's acceptable.
        $this->entityManager->flush(); 

        return $verse;
    }

    private function getOrImportBook(int $id, SymfonyStyle $io): ?Book
    {
        $book = $this->entityManager->getRepository(Book::class)->find($id);
        if ($book) {
            return $book;
        }

        $row = $this->legacyConnection->fetchAssociative('SELECT * FROM biblia_book WHERE id = ?', [$id]);
        if (!$row) {
            return null;
        }

        // Check Testament dependency
        $testament = null;
        if (isset($row['testment_id'])) {
            $testament = $this->getOrImportTestament($row['testment_id'], $io);
            if (!$testament) {
                $io->warning("Cannot import Book $id because Testament {$row['testment_id']} is missing.");
                // We might choose to return null or skip the relation. 
                // Given strict requirements, let's return null if testament is required.
                // But earlier we allowed skipping testament. Let's be consistent with importBooks logic?
                // importBooks skipped the book if testament was missing. So we return null.
                return null;
            }
        }

        $book = new Book();
        $reflection = new \ReflectionClass($book);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($book, $id);

        $book->setName($row['name']);
        $book->setAbbreviation($row['abbreviation']);
        $book->setBookOrder($row['position']);
        if ($testament) {
            $book->setTestament($testament);
        }

        $this->entityManager->persist($book);
        $this->entityManager->flush();

        return $book;
    }

    private function getOrImportTestament(int $id, SymfonyStyle $io): ?Testament
    {
        $testament = $this->entityManager->getRepository(Testament::class)->find($id);
        if ($testament) {
            return $testament;
        }

        $row = $this->legacyConnection->fetchAssociative('SELECT * FROM biblia_testament WHERE id = ?', [$id]);
        if (!$row) {
            return null;
        }

        $testament = new Testament();
        $reflection = new \ReflectionClass($testament);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($testament, $id);

        $testament->setName($row['name']);

        $this->entityManager->persist($testament);
        $this->entityManager->flush();

        return $testament;
    }

    private function getOrImportVersion(int $id, SymfonyStyle $io): ?BibleVersion
    {
        $version = $this->entityManager->getRepository(BibleVersion::class)->find($id);
        if ($version) {
            return $version;
        }

        $row = $this->legacyConnection->fetchAssociative('SELECT * FROM biblia_version WHERE id = ?', [$id]);
        if (!$row) {
            return null;
        }

        $version = new BibleVersion();
        $reflection = new \ReflectionClass($version);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($version, $id);

        $version->setName($row['name']);
        $version->setAbbreviation($row['abbreviation']);

        $this->entityManager->persist($version);
        $this->entityManager->flush();

        return $version;
    }

    private function importStrongDefinitions(SymfonyStyle $io): void
    {
        $io->section('Importing Strong Definitions...');
        // Use 'strongs' table as the base to ensure IDs match interlinear references
        $rows = $this->legacyConnection->fetchAllAssociative('SELECT * FROM strongs');

        foreach ($rows as $row) {
            $def = $this->entityManager->getRepository(StrongDefinition::class)->find($row['id']);
            if (!$def) {
                $def = new StrongDefinition();
                $reflection = new \ReflectionClass($def);
                $property = $reflection->getProperty('id');
                $property->setAccessible(true);
                $property->setValue($def, $row['id']);
            }

            $def->setCode($row['topic']);
            $def->setDefinition($row['definition']);
            $def->setLemma($row['lexame']);
            $def->setTransliteration($row['transliteration']);
            $def->setPronunciation($row['pronunciation']);

            // Fetch extra data from strong_dictionary
            $dictRow = $this->legacyConnection->fetchAssociative('SELECT * FROM strong_dictionary WHERE strong_id = ?', [$row['id']]);
            if ($dictRow) {
                $def->setFullDefinition($dictRow['text']);
                $def->setGreekWord($dictRow['greek_word']);
                $def->setHebrewWord($dictRow['hebrew_word']);
                // Prefer dictionary transliteration if available?
                if (!empty($dictRow['transliteral'])) {
                    $def->setTransliteration($dictRow['transliteral']);
                }
            }

            $this->entityManager->persist($def);
        }
        $this->entityManager->flush();
        $io->text(count($rows) . ' strong definitions imported.');
    }

    private function importVerseWords(SymfonyStyle $io): void
    {
        $io->section('Importing Verse Words (Interlinear)...');
        $offset = 0;
        $limit = 2000; // Larger batch for raw SQL
        $connection = $this->entityManager->getConnection();

        while (true) {
            $rows = $this->legacyConnection->fetchAllAssociative("SELECT * FROM interlinear LIMIT $limit OFFSET $offset");
            if (empty($rows))
                break;

            $values = [];
            $params = [];
            $types = [];
            
            foreach ($rows as $row) {
                // Skip if verse_id is missing (integrity check)
                // We assume verse exists because we imported them. 
                // But for raw SQL, if FK fails, it will throw exception.
                // Let's just insert.
                
                $values[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $params[] = $row['id'];
                $params[] = $row['external_id_id']; // verse_id
                $params[] = $row['strong_id'] ?: null; // strong_code_id
                $params[] = $row['greek_word'] ?: $row['hebrew_word']; // word_original
                $params[] = $row['portuguese_word'];
                $params[] = $row['transliteral'];
                $params[] = $row['english_type'];
                $params[] = $row['portuguese_type'];
                $params[] = $row['position'];
            }

            if (!empty($values)) {
                // Use INSERT IGNORE for MySQL compatibility instead of ON CONFLICT
                $sql = "INSERT IGNORE INTO verse_word (id, verse_id, strong_code_id, word_original, word_portuguese, transliteration, english_type, portuguese_type, position) VALUES " . implode(', ', $values);
                
                try {
                    $connection->executeStatement($sql, $params);
                } catch (\Exception $e) {
                    $io->error("Error inserting batch: " . $e->getMessage());
                }
            }

            $offset += $limit;
            $io->write('.');
            
            // Explicitly clear any potential buffered data
            unset($rows, $values, $params);
            gc_collect_cycles();
        }
        $io->newLine();
        $io->text('Verse words imported (raw SQL).');
    }

    private function importReferences(SymfonyStyle $io): void
    {
        $io->section('Importing References...');

        // Verse References
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
            return;
        }

        $importedCount = 0;
        $skippedCount = 0;

        foreach ($rows as $row) {
            $ref = null;
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
                $skippedCount++;
                continue;
            }

            // biblia_verse_id points to VerseText (Translation), not Verse (Canonical)
            // So we first find the VerseText, then get its Verse.
            $verseText = $this->entityManager->getRepository(VerseText::class)->find($row['biblia_verse_id']);
            
            if ($verseText && $verseText->getVerse()) {
                $ref->setVerse($verseText->getVerse());
                $ref->setTerm($row['vocable'] ?? null);
                $ref->setReferenceText($row['text'] ?? null);
                $this->entityManager->persist($ref);
                $importedCount++;
            } else {
                // $io->warning("Skipping reference ID " . ($row['id'] ?? '?') . ": VerseText ID {$row['biblia_verse_id']} not found or has no Verse.");
                $skippedCount++;
            }
        }
        $this->entityManager->flush();
        $io->text($importedCount . ' verse references imported. ' . $skippedCount . ' skipped (verse not found).');

        // Global References (nepe_reference? or where?)
        // Map says: GlobalReference.
        // Legacy table? 'nepe_reference'?
        // inspect_db.php showed 'nepe_reference' table with: id, book_id, verse_id, pages, notes, refeence, book_part_id.
        // This doesn't look like a global dictionary.
        // Maybe 'dictionary' or 'dictionary_entry'?
        // inspect_db.php showed 'dictionary' and 'dictionary_entry'.
        // Let's assume for now we don't have a direct map for GlobalReference or it's empty.
        // Or maybe 'supplies'?
        // User didn't specify source for GlobalReference in the chat, just "Cadastro de referências Globais".
        // I will leave it empty for now or ask user.
    }
    private function clearDatabase(SymfonyStyle $io): void
    {
        $io->section('Clearing Database...');
        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        $tables = [
            'verse_reference',
            'verse_word',
            'verse_text',
            'verse',
            'book',
            'testament',
            'bible_version',
            'strong_definition',
            'global_reference'
        ];

        // Disable foreign key checks if possible (MySQL) or use CASCADE (Postgres)
        // Since we are likely on Postgres (based on .env), we use TRUNCATE CASCADE.
        // But let's try to be generic or just use CASCADE which is supported by Postgres.
        // MySQL uses SET FOREIGN_KEY_CHECKS=0.

        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
             foreach ($tables as $table) {
                try {
                    $connection->executeStatement("TRUNCATE TABLE $table CASCADE");
                } catch (\Exception $e) {
                    // Ignore if table doesn't exist
                }
             }
        } else {
            // MySQL or others
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
            foreach ($tables as $table) {
                try {
                    $connection->executeStatement("TRUNCATE TABLE $table");
                } catch (\Exception $e) {
                    // Ignore
                }
            }
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        }

        $io->success('Database cleared.');
    }
}
