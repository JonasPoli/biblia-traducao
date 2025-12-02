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

        $io->title('Starting Import from Legacy Database');

        // 1. Import Testaments
        $this->importTestaments($io);

        // 2. Import Books
        $this->importBooks($io);

        // 3. Import Bible Versions
        $this->importBibleVersions($io);

        // 4. Import Verses (Canonical)
        $this->importVerses($io);

        // 5. Import Verse Texts (Translations)
        $this->importVerseTexts($io);

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
                }
            }

            $this->entityManager->persist($book);
        }
        $this->entityManager->flush();
        $io->text(count($rows) . ' books imported.');
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
                }
            }
            $this->entityManager->flush();
            $this->entityManager->clear(); // Clear to avoid memory leaks
            $offset += $limit;
            $io->write('.');
        }
        $io->newLine();
    }

    private function importVerseTexts(SymfonyStyle $io): void
    {
        $io->section('Importing Verse Texts...');
        $offset = 0;
        $limit = 500; // Reduce batch size

        while (true) {
            $rows = $this->legacyConnection->fetchAllAssociative("SELECT * FROM biblia_verse LIMIT $limit OFFSET $offset");
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
                    continue;
                }

                $verse = $this->entityManager->getRepository(Verse::class)->find($row['external_id_id']);
                $version = $this->entityManager->getRepository(BibleVersion::class)->find($row['version_id']);

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
                }
            }
            $this->entityManager->flush();
            $this->entityManager->clear();
            gc_collect_cycles(); // Force garbage collection
            $offset += $limit;
            $io->write('.');
        }
        $io->newLine();
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
        $limit = 1000;

        while (true) {
            $rows = $this->legacyConnection->fetchAllAssociative("SELECT * FROM interlinear LIMIT $limit OFFSET $offset");
            if (empty($rows))
                break;

            foreach ($rows as $row) {
                $word = $this->entityManager->getRepository(VerseWord::class)->find($row['id']);
                if (!$word) {
                    $word = new VerseWord();
                    $reflection = new \ReflectionClass($word);
                    $property = $reflection->getProperty('id');
                    $property->setAccessible(true);
                    $property->setValue($word, $row['id']);
                }

                $verse = $this->entityManager->getRepository(Verse::class)->find($row['external_id_id']);
                if ($verse) {
                    $word->setVerse($verse);
                    $word->setPosition($row['position']);

                    if ($row['strong_id']) {
                        $strong = $this->entityManager->getRepository(StrongDefinition::class)->find($row['strong_id']);
                        if ($strong) {
                            $word->setStrongCode($strong);
                        }
                    }

                    $word->setWordOriginal($row['greek_word'] ?: $row['hebrew_word']); // Use whichever is present
                    $word->setWordPortuguese($row['portuguese_word']);
                    $word->setTransliteration($row['transliteral']);
                    $word->setEnglishType($row['english_type']);
                    $word->setPortugueseType($row['portuguese_type']);

                    $this->entityManager->persist($word);
                }
            }
            $this->entityManager->flush();
            $this->entityManager->clear();
            $offset += $limit;
            $io->write('.');
        }
        $io->newLine();
    }

    private function importReferences(SymfonyStyle $io): void
    {
        $io->section('Importing References...');

        // Verse References
        $rows = $this->legacyConnection->fetchAllAssociative('SELECT * FROM biblia_verse_reference');
        foreach ($rows as $row) {
            $ref = $this->entityManager->getRepository(VerseReference::class)->find($row['id']);
            if (!$ref) {
                $ref = new VerseReference();
                $reflection = new \ReflectionClass($ref);
                $property = $reflection->getProperty('id');
                $property->setAccessible(true);
                $property->setValue($ref, $row['id']);
            }

            $verse = $this->entityManager->getRepository(Verse::class)->find($row['biblia_verse_id']);
            if ($verse) {
                $ref->setVerse($verse);
                $ref->setTerm($row['vocable']);
                $ref->setReferenceText($row['text']);
                $this->entityManager->persist($ref);
            }
        }
        $this->entityManager->flush();
        $io->text(count($rows) . ' verse references imported.');

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
}
