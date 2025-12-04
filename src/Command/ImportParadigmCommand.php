<?php

namespace App\Command;

use App\Entity\Paradigm;
use App\Repository\ParadigmRepository;
use App\Repository\VerseTextRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-paradigm',
    description: 'Imports paradigm data from VerseText (Version 22)',
)]
class ImportParadigmCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private VerseTextRepository $verseTextRepository,
        private ParadigmRepository $paradigmRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Importing Paradigm Data');

        // Increase memory limit for this command
        ini_set('memory_limit', '512M');

        // 1. Clear existing data
        $io->section('Clearing existing paradigm data...');
        $this->em->createQuery('DELETE FROM App\Entity\Paradigm')->execute();
        $io->success('Paradigm table cleared.');

        // 2. Fetch all VerseText for Version 22 (Almeida)
        $io->section('Fetching verse texts...');
        // We'll process in batches to avoid memory issues
        $batchSize = 200;
        $offset = 0;
        
        // In-memory aggregation: [key => count]
        // Key format: "foreignWord|translation|strongCode|rmac|wordClass"
        $aggregatedData = [];

        while (true) {
            $verseTexts = $this->verseTextRepository->findBy(
                ['version' => 22],
                ['id' => 'ASC'],
                $batchSize,
                $offset
            );

            if (empty($verseTexts)) {
                break;
            }

            foreach ($verseTexts as $vt) {
                $text = $vt->getText();
                $this->processVerseText($text, $aggregatedData);
            }

            // Detach objects to free memory
            $this->em->clear();

            $offset += $batchSize;
            $io->write('.');
            if ($offset % 1000 === 0) {
                $io->write(" ($offset processed)");
            }
        }
        $io->newLine();
        $io->success("Processed all verse texts. Found " . count($aggregatedData) . " unique paradigms.");

        // 3. Save to Database
        $io->section('Saving to database...');
        $count = 0;
        foreach ($aggregatedData as $key => $amount) {
            [$foreignWord, $translation, $strongCode, $rmac, $wordClass] = explode('|', $key);

            $paradigm = new Paradigm();
            $paradigm->setForeignWord($foreignWord);
            $paradigm->setTranslation($translation);
            $paradigm->setStrongCode($strongCode);
            $paradigm->setRmac($rmac === 'null' ? null : $rmac);
            $paradigm->setWordClass($wordClass === 'null' ? null : $wordClass);
            $paradigm->setAmount($amount);

            $this->em->persist($paradigm);
            $count++;

            if ($count % 500 === 0) {
                $this->em->flush();
                $this->em->clear();
            }
        }

        $this->em->flush();
        $this->em->clear();

        $io->success("Successfully imported $count paradigm records.");

        return Command::SUCCESS;
    }

    private function processVerseText(string $text, array &$aggregatedData): void
    {
        // Regex to match the pattern:
        // translation<S>STRONG</S> <n>original</n><S>STRONG</S> <S>RMAC</S>
        // Note: The structure can vary.
        // Example: sabei<S>G1097</S> <n>γινώσκω</n><S>G1097</S> <S>G5720</S>
        
        // We need to split the text into chunks that represent "words" or "phrases" with their metadata.
        // A simple regex might not cover all cases perfectly due to nesting or adjacent tags, 
        // but let's try to capture the pattern described.
        
        // Strategy:
        // 1. Split by spaces? No, "o pecador" is two words but one unit.
        // 2. Look for the pattern: TEXT<S>STRONG</S>...
        
        // Let's try to match the full block for a word:
        // (.*?)<S>(G\d+)<\/S>\s*<n>(.*?)<\/n><S>\2<\/S>(?:\s*<S>(G\d+)<\/S>)?
        
        // Explanation:
        // (.*?)            -> Group 1: Translation (lazy)
        // <S>(G\d+)<\/S>   -> Group 2: Strong ID (first occurrence)
        // \s*<n>(.*?)<\/n> -> Group 3: Original Word
        // <S>\2<\/S>       -> Match Strong ID again (validation)
        // (?:\s*<S>(G\d+)<\/S>)? -> Group 4: RMAC (optional)

        // However, the example "que<S>G3754</S> <n>ὅτι</n><S>G3754</S>" has no RMAC.
        // And "o pecador<S>G268</S> <n>ἀμαρτωλός</n><S>G268</S>" also no RMAC.
        
        // Regex:
        // ([^<]+)<S>(G\d+)<\/S>\s*<n>([^<]+)<\/n><S>\2<\/S>(?:\s*<S>(G\d+)<\/S>)?
        
        // Use negative lookahead to ensure we don't consume <S> or <n> tags inside the translation capture group
        // This prevents capturing "skipped" words or malformed tags from previous segments
        preg_match_all('/((?:(?!<S>|<n>).)*?)<S>(G\d+)<\/S>\s*<n>([^<]+)<\/n><S>\2<\/S>(?:\s*<S>(G\d+)<\/S>)?/us', $text, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $translation = $match[1];
            // Aggressively clean translation
            // 1. Remove specific patterns like PB/>, /S>, etc. (case insensitive)
            $translation = preg_replace('/(?:<PB\/>|PB\/>|\/S>|<br\s*\/?>|[.,!?;:])+/i', ' ', $translation);
            
            // 2. Remove anything that looks like a tag or partial tag
            $translation = preg_replace('/<[^>]*>/', ' ', $translation);
            
            // 3. Strip any remaining HTML-like tags
            $translation = strip_tags($translation);
            
            // 4. Remove any remaining < or > characters and extra spaces
            $translation = str_replace(['<', '>'], ' ', $translation);
            // 5. Remove any Strong codes (G1234, H1234) that might have leaked into translation
            $translation = preg_replace('/[GH]\d+/', '', $translation);
            
            // 6. Remove standalone "S" or "N" that might be remnants of tags
            $translation = preg_replace('/\b[SN]\b/', '', $translation);
            
            $translation = preg_replace('/\s+/', ' ', $translation); // Collapse multiple spaces
            $translation = trim($translation);
            $strongCode = trim($match[2]);
            $foreignWord = trim($match[3]);
            $rmac = isset($match[4]) ? trim($match[4]) : null;
            $wordClass = null; // Not extracted from this text format currently

            // Normalize
            $translation = mb_strtoupper($translation);
            
            // Key for aggregation
            // Use 'null' string for null values to allow array key usage
            $key = sprintf(
                '%s|%s|%s|%s|%s',
                $foreignWord,
                $translation,
                $strongCode,
                $rmac ?? 'null',
                $wordClass ?? 'null'
            );

            if (!isset($aggregatedData[$key])) {
                $aggregatedData[$key] = 0;
            }
            $aggregatedData[$key]++;
        }
    }
}
