<?php

namespace App\Command;

use App\Repository\BookRepository;
use App\Repository\VerseRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-missing-verses',
    description: 'Checks for missing verses (gaps) in a book',
)]
class CheckMissingVersesCommand extends Command
{
    public function __construct(
        private BookRepository $bookRepository,
        private VerseRepository $verseRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('bookId', InputArgument::REQUIRED, 'The ID of the book to check');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $bookId = $input->getArgument('bookId');

        $book = $this->bookRepository->find($bookId);
        if (!$book) {
            $io->error('Book not found');
            return Command::FAILURE;
        }

        $io->title('Checking for missing verses in: ' . $book->getName());

        $verses = $this->verseRepository->findBy(['book' => $book], ['chapter' => 'ASC', 'verse' => 'ASC']);
        // Assuming version ID 1 for now, or fetch from constant
        $versionId = 1; // Replace with actual version ID if needed
        
        // Fetch all texts for this book to compare
        // We can't easily inject VerseTextRepository here without changing constructor
        // So we'll rely on lazy loading or just check if we can fetch it.
        // Actually, let's just check gaps first.
        
        // BETTER: Use the repository passed in constructor
        // We need to add VerseTextRepository to constructor
        
        $missing = [];
        $missingTexts = [];
        $lastChapter = 0;
        $lastVerse = 0;

        foreach ($verses as $verse) {
            $currentChapter = $verse->getChapter();
            $currentVerse = $verse->getVerse();
            
            // Check for gaps
            if ($currentChapter > $lastChapter) {
                if ($currentVerse > 1) {
                    for ($v = 1; $v < $currentVerse; $v++) {
                        $missing[] = sprintf("Chapter %d, Verse %d", $currentChapter, $v);
                    }
                }
                $lastChapter = $currentChapter;
                $lastVerse = $currentVerse;
            } else {
                if ($currentVerse > $lastVerse + 1) {
                    for ($v = $lastVerse + 1; $v < $currentVerse; $v++) {
                        $missing[] = sprintf("Chapter %d, Verse %d", $currentChapter, $v);
                    }
                }
                $lastVerse = $currentVerse;
            }
            
            // Check for missing text
            if ($verse->getVerseTexts()->isEmpty()) {
                 $missingTexts[] = sprintf("ID: %d (Ch %d, V %d) has NO text", $verse->getId(), $currentChapter, $currentVerse);
            }
        }

        $outputContent = "";

        if (empty($missing)) {
            $io->success('No missing verses found (based on sequential gaps).');
            $outputContent .= "No missing verses found (gaps) for " . $book->getName() . "\n";
        } else {
            $io->warning(sprintf('Found %d missing verses (gaps).', count($missing)));
            $outputContent .= "Missing verses (gaps) for " . $book->getName() . ":\n" . implode("\n", $missing) . "\n";
        }
        
        if (!empty($missingTexts)) {
            $io->warning(sprintf('Found %d verses with NO TEXT.', count($missingTexts)));
            $outputContent .= "\nVerses with NO TEXT:\n" . implode("\n", $missingTexts);
        } else {
             $outputContent .= "\nAll verses have at least one text entry.\n";
        }
        
        file_put_contents('missing_verses.txt', $outputContent);
        $io->success('List saved to missing_verses.txt');

        return Command::SUCCESS;
    }
}
