<?php

namespace App\Command;

use App\Entity\Book;
use App\Entity\LouwNida;
use App\Entity\LouwNidaSense;
use App\Entity\StrongDefinition;
use App\Entity\Verse;
use App\Repository\BookRepository;
use App\Repository\LouwNidaRepository;
use App\Repository\LouwNidaSenseRepository;
use App\Repository\StrongDefinitionRepository;
use App\Repository\VerseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-interlinear',
    description: 'Creates an HTML file with the Interlinear Bible.',
)]
class CreateInterlinearCommand extends Command
{
    public function __construct(
        private BookRepository $bookRepository,
        private VerseRepository $verseRepository,
        private LouwNidaRepository $louwNidaRepository,
        private LouwNidaSenseRepository $louwNidaSenseRepository,
        private StrongDefinitionRepository $strongDefinitionRepository,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'split-by-book',
            's',
            InputOption::VALUE_NONE,
            'If set, creates a separate HTML file for each book.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '1G');

        $io = new SymfonyStyle($input, $output);
        $io->title('Creating Interlinear Bible HTML');

        $splitByBook = $input->getOption('split-by-book');
        $books = $this->bookRepository->findBy([], ['testament' => 'ASC', 'bookOrder' => 'ASC']);
        
        $progressBar = new ProgressBar($output, count($books));
        $progressBar->start();

        $singleFileHandle = null;

        if (!$splitByBook) {
            $filePath = 'biblia-interlinear.html';
            $singleFileHandle = fopen($filePath, 'w');
            if (!$singleFileHandle) {
                $io->error("Could not open file $filePath for writing.");
                return Command::FAILURE;
            }
            $this->writeHtmlHeader($singleFileHandle);
        }

        foreach ($books as $book) {
            $fileHandle = $singleFileHandle;
            
            if ($splitByBook) {
                $fileName = sprintf('%d_%s.html', $book->getBookOrder(), str_replace(' ', '_', $book->getName()));
                // Sanitize filename if needed, but basic replacement should suffice for now.
                $fileHandle = fopen($fileName, 'w');
                if (!$fileHandle) {
                    $io->error("Could not open file $fileName for writing.");
                    return Command::FAILURE;
                }
                $this->writeHtmlHeader($fileHandle, $book->getName());
            }

            fwrite($fileHandle, "<h1>" . $book->getName() . "</h1>\n");

            $testamentId = $book->getTestament()->getId();

            if ($testamentId === 1) { // Old Testament
                $this->processOldTestament($book, $fileHandle);
            } else { // New Testament
                $this->processNewTestament($book, $fileHandle);
            }
            
            if ($splitByBook) {
                $this->writeHtmlFooter($fileHandle);
                fclose($fileHandle);
            }
            
            // Advance progress bar
            $progressBar->advance();

            // Clear entity manager to avoid memory leaks
            $this->entityManager->clear();
            
            if (!$splitByBook) {
                // Flush file buffer to disk periodically
                fflush($singleFileHandle);
            }
        }

        if (!$splitByBook) {
            $this->writeHtmlFooter($singleFileHandle);
            fclose($singleFileHandle);
            $io->success("File 'biblia-interlinear.html' created successfully.");
        } else {
             $io->success("Files created successfully (one per book).");
        }

        $progressBar->finish();
        $io->newLine(2);

        return Command::SUCCESS;
    }

    private function writeHtmlHeader($fileHandle, string $title = 'Biblia Interlinear'): void
    {
        fwrite($fileHandle, "<!DOCTYPE html>\n<html>\n<head>\n<meta charset='UTF-8'>\n<title>$title</title>\n</head>\n<body>\n");
    }

    private function writeHtmlFooter($fileHandle): void
    {
        fwrite($fileHandle, "</body>\n</html>");
    }

    private function processOldTestament(Book $book, $fileHandle): void
    {
        // To avoid loading all verses into memory, we could iterate by chapter or iterate generic results.
        // However, standard Doctrine findBy on a single book's verses shouldn't explode memory for one book 
        // if we clear EM after each book. Genesis has ~1500 verses, manageable.
        // If strict memory management is needed, we would use an IterableResult or paginator.
        // For now, finding by book and clearing EM per book (in execute loop) is a good balance.
        
        $verses = $this->verseRepository->findBy(['book' => $book], ['chapter' => 'ASC', 'verse' => 'ASC']);
        
        $currentChapter = 0;

        foreach ($verses as $verse) {
            if ($verse->getChapter() !== $currentChapter) {
                $currentChapter = $verse->getChapter();
                fwrite($fileHandle, "\n<h2>" . $book->getName() . " - Capítulo " . $currentChapter . "</h2>\n");
            }

            $verseWords = $verse->getVerseWords();
            
            // H3 Header
            $headerLine = "<h3>";
            foreach ($verseWords as $word) {
                $pt = $word->getWordPortuguese() ?? '';
                $original = $word->getWordOriginal() ?? '';
                $headerLine .= trim($pt) . " <i>(" . trim($original) . ")</i> ";
            }
            $headerLine .= "</h3>\n";
            fwrite($fileHandle, $headerLine);

            // Detailed Word Map
            foreach ($verseWords as $word) {
                $original = $word->getWordOriginal() ?? '';
                $strongCode = $word->getStrongDefinition() ? $word->getStrongDefinition()->getCode() : ($word->getStrongCode() ?? '');
                $transliteration = $word->getTransliteration() ?? '';
                $ptType = $word->getPortugueseType() ?? '';
                $ptWord = $word->getWordPortuguese() ?? '';

                fwrite($fileHandle, "<h4>" . $original . "</h4>\n");
                
                // Strong Link
                // Note: User prompt asked for <p>Strong: <strong>verse_world.strong_code</strong></p>
                // Depending on entity property name: $strongCode handles it.
                fwrite($fileHandle, "<p>Strong: <strong>" . $strongCode . "</strong></p>\n");
                
                // Format: <p>word_portuguese, espaço hifém espaço, a  verse_world.tranliteration, espaço hifém espaço,   verse_world.portuguese_type.</p>
                fwrite($fileHandle, "<p>" . $ptWord . " - " . $transliteration . " - " . $ptType . "</p>\n");

                $strongDef = $word->getStrongDefinition();
                if ($strongDef) {
                    $def = $strongDef->getDefinition() ?? '';
                    $fullDef = $strongDef->getFullDefinition() ?? '';
                    if ($def) fwrite($fileHandle, "<p>" . $def . "</p>\n");
                    if ($fullDef) fwrite($fileHandle, "<p>" . $fullDef . "</p>\n");
                }
                fwrite($fileHandle, "<hr>\n");
            }
        }
    }

    private function processNewTestament(Book $book, $fileHandle): void
    {
        $louwNidaItems = $this->louwNidaRepository->findBy(
            ['book' => $book->getId()],
            ['chapter' => 'ASC', 'verse' => 'ASC', 'ogntSort' => 'ASC']
        );

        if (empty($louwNidaItems)) {
            return;
        }

        $currentChapter = 0;
        
        // Grouping in memory is fine for a single book (e.g. Matthew ~1000 verses).
        $verseGroups = [];
        foreach ($louwNidaItems as $item) {
            $c = $item->getChapter();
            $v = $item->getVerse();
            $key = "$c:$v";
            if (!isset($verseGroups[$key])) {
                $verseGroups[$key] = [];
            }
            $verseGroups[$key][] = $item;
        }

        foreach ($verseGroups as $key => $items) {
            /** @var LouwNida $firstItem */
            $firstItem = $items[0];
            $c = $firstItem->getChapter();
            
            if ($c !== $currentChapter) {
                $currentChapter = $c;
                fwrite($fileHandle, "\n<h2>" . $book->getName() . " - Capítulo " . $currentChapter . "</h2>\n");
            }

            // H3 Header
            $headerText = "";
            foreach ($items as $item) {
                // ogntA followed by space
                $headerText .= ($item->getOgntA() ?? '') . " ";
            }
            fwrite($fileHandle, "<h3>" . trim($headerText) . "</h3>\n");

            // Word Details
            foreach ($items as $item) {
                $ogntA = $item->getOgntA() ?? '';
                fwrite($fileHandle, "<h4>" . $ogntA . "</h4>\n");

                $sn = $item->getSn();
                
                // Find StrongDefinition
                if ($sn) {
                    $strongDef = $this->strongDefinitionRepository->findOneBy(['code' => $sn]);
                     // "Se encontrado" logic
                    if ($strongDef) {
                        fwrite($fileHandle, "<p>Strong: <strong>" . $strongDef->getCode() . "</strong></p>\n");
                        fwrite($fileHandle, "<p>" . ($strongDef->getTransliteration() ?? '') . " - " . ($strongDef->getPronunciation() ?? '') . "</p>\n");
                    }
                }

                // LouwNidaSense
                $lnNumber = $item->getLnNumber();
                if ($lnNumber && $sn) {
                    fwrite($fileHandle, "<p>Louw-Nida: <strong>" . $lnNumber . "</strong></p>\n");
                     $senses = $this->louwNidaSenseRepository->findBy([
                         'louwNidaNumber' => $lnNumber,
                         'strongCode' => $sn
                     ]);
                     
                     foreach ($senses as $sense) {
                         $ideia = $sense->getIdeiaCentralPtBr() ?? '';
                         $sentido = $sense->getSentidoSemanticoPtBr() ?? '';
                         if ($ideia) fwrite($fileHandle, "<p>" . $ideia . "</p>\n");
                         if ($sentido) fwrite($fileHandle, "<p>" . $sentido . "</p>\n");
                     }
                }
                fwrite($fileHandle, "<hr>\n");
            }
        }
    }
}
