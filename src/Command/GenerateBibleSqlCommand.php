<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use Doctrine\Persistence\ManagerRegistry;

#[AsCommand(
    name: 'app:generate-bible-sql',
    description: 'Generates SQL INSERT statements for Bible translations from markdown files.',
)]
class GenerateBibleSqlCommand extends Command
{
    private Connection $connection;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct();
        $this->connection = $registry->getConnection('legacy');
    }

    protected function configure(): void
    {
        $this
            ->addArgument('inputFile', InputArgument::REQUIRED, 'Path to the markdown file')
            ->addArgument('bookId', InputArgument::REQUIRED, 'ID of the book in the database')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $inputFile = $input->getArgument('inputFile');
        $bookId = (int) $input->getArgument('bookId');
        $versionId = 17; // Haroldo Dutra

        if (!file_exists($inputFile)) {
            $io->error(sprintf('File not found: %s', $inputFile));
            return Command::FAILURE;
        }

        $lines = file($inputFile, FILE_IGNORE_NEW_LINES);
        $sqlStatements = [];
        $processedCount = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Regex to match "Chapter:Verse - Text"
            // Handles potential " \- " or just " - " or "\- " 
            if (preg_match('/^(\d+):(\d+)\s*(?:\\\\?-)?\s*(.*)$/', $line, $matches)) {
                $chapter = (int) $matches[1];
                $verseNum = (int) $matches[2];
                $rawText = $matches[3];

                // Clean text
                $text = $this->cleanText($rawText);

                // Fetch verse ID
                $verseId = $this->fetchVerseId($bookId, $chapter, $verseNum);

                if ($verseId) {
                    // Escape single quotes for SQL
                    $safeText = str_replace("'", "''", $text);
                    $sqlStatements[] = sprintf(
                        "INSERT INTO verse_text (verse_id, version_id, text) VALUES (%d, %d, '%s');",
                        $verseId,
                        $versionId,
                        $safeText
                    );
                    $processedCount++;
                } else {
                    $io->warning(sprintf('Verse ID not found for %d:%d', $chapter, $verseNum));
                }
            }
        }

        // Generate output filename
        $pathInfo = pathinfo($inputFile);
        $outputFile = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.sql';

        if (file_put_contents($outputFile, implode("\n", $sqlStatements))) {
            $io->success(sprintf('Generated %d SQL statements in %s', $processedCount, $outputFile));
            return Command::SUCCESS;
        } else {
            $io->error('Failed to write output file.');
            return Command::FAILURE;
        }
    }

    private function cleanText(string $text): string
    {
        // Remove leading "- " or "\- "
        $text = preg_replace('/^\\\\?- /', '', $text);

        // Convert "\! " to "! "
        $text = str_replace('\! ', '! ', $text);

        // Convert "**text**" to "<b>text</b>"
        $text = preg_replace('/\*\*(.*?)\*\*/', '<b>$1</b>', $text);

        // Clean other common escapes if any
        $text = str_replace('\-', '-', $text);

        return trim($text);
    }

    private function fetchVerseId(int $bookId, int $chapter, int $verse): ?int
    {
        $sql = 'SELECT id FROM biblia_verse WHERE book_id = :bookId AND chapter = :chapter AND verse = :verse';
        $result = $this->connection->fetchOne($sql, [
            'bookId' => $bookId,
            'chapter' => $chapter,
            'verse' => $verse,
        ]);

        return $result !== false ? (int) $result : null;
    }
}
