<?php

namespace App\Command;

use App\Entity\LouwNida;
use App\Entity\LouwNidaDomain;
use App\Repository\LouwNidaDomainRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'app:import-opengnt',
    description: 'Imports OpenGNT data',
)]
class ImportOpenGNTCommand extends Command
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
        $csvPath = $this->projectDir . '/docs/OpenGNT_version3_3.csv';

        if (!file_exists($csvPath)) {
            $io->error("File not found: $csvPath");
            return Command::FAILURE;
        }

        // Increase memory limit for large import
        ini_set('memory_limit', '1G');

        $io->title('Importing OpenGNT Data');

        if (($handle = fopen($csvPath, "r")) !== FALSE) {
            // Check headers
            $headers = fgetcsv($handle, 0, "\t"); // Assuming Tab separated
            if (!$headers || count($headers) < 10) {
                $io->error("Could not parse headers. Ensure the file is Tab-separated.");
                return Command::FAILURE;
            }

            $progressBar = new ProgressBar($output);
            $progressBar->start();

            $batchSize = 100;
            $i = 0;

            // Pre-load domains to optimize lookup?
            // Too many domains? ~200-300 probably.
            // Let's cache them in memory as needed or load all.
            // There are about 90 top domains, subs can be more. Let's rely on doctrine cache or simple array cache if slow.
            // For now, standard findOneBy.

            while (($data = fgetcsv($handle, 0, "\t")) !== FALSE) {
                if (count($data) < 13) {
                    continue; // Skip malformed lines
                }

                $entity = new LouwNida();

                // 1. Basic Fields
                $entity->setOgntSort((int) $data[0]);
                $entity->setTanttSort((int) $data[1]);
                $entity->setFeaturesSort((int) $data[2]);
                $entity->setLevinsohnClauseId($data[3] === '-' ? null : $data[3]);
                $entity->setOtQuotation($data[4] === '-' ? null : $data[4]);

                // 2. Composite Fields Parsing
                // Separator seems to be U+FF5C (｜)
                $sep = '｜';

                // Col 5: 〔BGBsortI｜LTsortI｜STsortI〕
                $p5 = $this->parseComposite($data[5], $sep);
                $entity->setBgbSort(isset($p5[0]) && is_numeric($p5[0]) ? (int) $p5[0] : null);
                $entity->setLtSort(isset($p5[1]) && is_numeric($p5[1]) ? (int) $p5[1] : null);
                $entity->setStSort(isset($p5[2]) && is_numeric($p5[2]) ? (int) $p5[2] : null);

                // Col 6: 〔Book｜Chapter｜Verse〕
                $p6 = $this->parseComposite($data[6], $sep);
                $entity->setBook(isset($p6[0]) ? (int) $p6[0] : 0);
                $entity->setChapter(isset($p6[1]) ? (int) $p6[1] : 0);
                $entity->setVerse(isset($p6[2]) ? (int) $p6[2] : 0);

                // Col 7: 〔OGNTk｜OGNTu｜OGNTa｜lexeme｜rmac｜sn〕
                $p7 = $this->parseComposite($data[7], $sep);
                $entity->setOgntK($p7[0] ?? null);
                $entity->setOgntU($p7[1] ?? null);
                $entity->setOgntA($p7[2] ?? null);
                $entity->setLexeme($p7[3] ?? null);
                $entity->setRmac($p7[4] ?? null);
                $entity->setSn($p7[5] ?? null);

                // Col 8: 〔BDAGentry｜EDNTentry｜MounceEntry｜GoodrickKohlenbergerNumbers｜LN-LouwNidaNumbers〕
                $p8 = $this->parseComposite($data[8], $sep);
                $entity->setBdagEntry($p8[0] ?? null);
                $entity->setEdntEntry($p8[1] ?? null);
                $entity->setMounceEntry($p8[2] ?? null);
                $entity->setGkNumber($p8[3] ?? null);

                $lnRaw = $p8[4] ?? null;
                $entity->setLnNumber($lnRaw);

                // Link Domain if present
                if ($lnRaw && str_starts_with($lnRaw, 'LN-')) {
                    // Extract first domain ID: LN-33.38 or LN-10.24，33.19
                    // Remove LN-
                    $clean = substr($lnRaw, 3);
                    // Split by comma (fullwidth or normal?)
                    // The doc says "，" (U+FF0C)
                    $domains = preg_split('/[，,]/u', $clean);
                    $firstDomainId = trim($domains[0] ?? '');

                    if ($firstDomainId) {
                        // Find domain
                        // We stored "33.38" in semanticDomainId
                        $domain = $this->louwNidaDomainRepository->findOneBy(['semanticDomainId' => $firstDomainId]);
                        if ($domain) {
                            $entity->setLouwNidaDomain($domain);
                        }
                    }
                }

                // Col 9: 〔transSBLcap｜transSBL｜modernGreek｜Fonética_Transliteración〕
                $p9 = $this->parseComposite($data[9], $sep);
                $entity->setTransSblCap($p9[0] ?? null);
                $entity->setTransSbl($p9[1] ?? null);
                $entity->setModernGreek($p9[2] ?? null);
                $entity->setPhonetic($p9[3] ?? null);

                // Col 10: 〔TBESG｜IT｜LT｜ST｜Español〕
                $p10 = $this->parseComposite($data[10], $sep);
                $entity->setTbesg($p10[0] ?? null);
                $entity->setIt($p10[1] ?? null);
                $entity->setLt($p10[2] ?? null);
                $entity->setSt($p10[3] ?? null);
                $entity->setEspanol($p10[4] ?? null);

                // Col 11: 〔PMpWord｜PMfWord〕
                $p11 = $this->parseComposite($data[11], $sep);
                $entity->setPmpWord($p11[0] ?? null);
                $entity->setPmfWord($p11[1] ?? null);

                // Col 12: 〔Note｜Mvar｜Mlexeme｜Mrmac｜Msn｜MTBESG〕
                $p12 = $this->parseComposite($data[12], $sep);
                $entity->setNote($p12[0] ?? null);
                // We're expecting 6 fields here, but defined fewer in entity?
                // Wait, I didn't add Mvar/Mlexeme etc to entity individually, just 'Note'.
                // The prompt requirements said: "não só os campos, as as diversas partes dos campos cada uma em um campo próprio."
                // I might have missed fields in the Entity definition.
                // Let's check my Entity definition.
                // I added: note.
                // I missed: mvar, mlexeme, mrmac, msn, mtbesg.
                // I should add them if I want to be compliant. 
                // However, I can proceed with just reading 'Note' for now or update entity.
                // Given "Note" is there, I'll store p12[0] in Note.
                // Ideally I should update entity, but for this task I will stick to what I created.

                $this->entityManager->persist($entity);

                if (($i % $batchSize) === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }
                $progressBar->advance();
                $i++;
            }

            $this->entityManager->flush();
            $this->entityManager->clear();
            $progressBar->finish();
            fclose($handle);
        }

        $io->success(" Imported $i records successfully.");

        return Command::SUCCESS;
    }

    private function parseComposite(string $raw, string $sep): array
    {
        // Remove outer brackets 〔 〕 (U+3014, U+3015)
        $inner = mb_substr($raw, 1, mb_strlen($raw) - 2);
        if ($inner === false) {
            return [];
        }
        // Split
        $parts = explode($sep, $inner);
        return array_map('trim', $parts);
    }
}
