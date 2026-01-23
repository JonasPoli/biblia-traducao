<?php

namespace App\Command;

use App\Entity\LouwNidaSense;
use App\Repository\LouwNidaRepository;
use App\Repository\LouwNidaSenseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:populate-louw-nida-sense',
    description: 'Populates LouwNidaSense table using GPT based on LouwNida records',
)]
class PopulateLouwNidaSenseCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LouwNidaRepository $louwNidaRepository,
        private LouwNidaSenseRepository $louwNidaSenseRepository,
        private HttpClientInterface $client
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit number of items to process', 10)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do not write to database')
            ->addOption('with-gpt', null, InputOption::VALUE_NONE, 'Fetch data from GPT. If not set, creates empty records.')
            ->addOption('mock', null, InputOption::VALUE_NONE, 'Use mock data instead of OpenAI API (requires --with-gpt)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = $input->getOption('limit');
        $dryRun = $input->getOption('dry-run');
        $withGpt = $input->getOption('with-gpt');
        $mock = $input->getOption('mock');

        // Check for API Key if using GPT and not mocking
        $apiKey = $_ENV['OPENAI_API_KEY'] ?? $_SERVER['OPENAI_API_KEY'] ?? null;
        if ($withGpt && !$mock && !$apiKey) {
            $io->error('OPENAI_API_KEY not found in environment.');
            return Command::FAILURE;
        }

        $io->title('Populating LouwNidaSense table (' . ($withGpt ? ($mock ? 'WITH MOCK DATA' : 'WITH GPT') : 'SKELETON ONLY') . ')');

        // Allow more memory for caching
        ini_set('memory_limit', '1G');

        // 1. Pre-load existing LouwNidaSense to avoid N+1 queries.
        // Map: "LN|SN" => ['hasData' => bool, 'entity' => LouwNidaSense|null]
        $io->text('Pre-loading existing LouwNidaSense records...');
        $existingSenses = $this->louwNidaSenseRepository->findAll();
        $senseMap = [];
        foreach ($existingSenses as $js) {
            $key = $js->getLouwNidaNumber() . '|' . $js->getStrongCode();
            $hasData = !empty($js->getIdeiaCentralPtBr()) && !empty($js->getSentidoSemanticoPtBr());
            $senseMap[$key] = ['hasData' => $hasData, 'entity' => $js];
        }
        $io->text(sprintf('Loaded %d existing senses.', count($senseMap)));
        $this->entityManager->clear(); // Clear UoW to avoid keeping all Senses in memory

        // 2. Iterate LouwNida entities
        $query = $this->entityManager->createQuery('SELECT l FROM App\Entity\LouwNida l');
        $iterableResult = $query->toIterable();

        $processed = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        $progressBar = new ProgressBar($output, $limit > 0 ? $limit : 0);
        $progressBar->start();

        foreach ($iterableResult as $louwNida) {
            if ($limit > 0 && $processed >= $limit) {
                break;
            }

            $rawLnNumber = $louwNida->getLnNumber();
            $sn = $louwNida->getSn();

            if (!$rawLnNumber || !$sn) {
                continue;
            }

            // Handle splitting "LN-92.20, 93.30" -> ["LN-92.20", "LN-93.30"]
            // 1. Remove optional "LN-" prefix temporarily to clean up
            $cleanRaw = $rawLnNumber;
            // 2. Split by comma (standard or fullwidth)
            $parts = preg_split('/[，,]/u', $cleanRaw);

            foreach ($parts as $part) {
                $part = trim($part);
                if (empty($part)) {
                    continue;
                }

                // Ensure "LN-" prefix presence
                if (!str_starts_with($part, 'LN-')) {
                    $lnNumber = 'LN-' . $part;
                } else {
                    $lnNumber = $part;
                }

                $key = $lnNumber . '|' . $sn;
                $sense = null;
                $isNew = false;
                $needsUpdate = false;

                // Check Memory Map
                if (isset($senseMap[$key])) {
                    $mapEntry = $senseMap[$key];
                    if ($mapEntry['hasData']) {
                        // Already exists and populated. Skip.
                        $skipped++;
                        continue;
                    }
                    // Exists but empty. If we have --with-gpt, we might update it.
                    if (!$withGpt) {
                        $skipped++;
                        continue;
                    }

                    // Needs update. Fetch object.
                    $sense = $this->louwNidaSenseRepository->findOneBy(['louwNidaNumber' => $lnNumber, 'strongCode' => $sn]);
                    if (!$sense) {
                        // Fallback if not found (unexpected)
                        $sense = new LouwNidaSense();
                        $sense->setLouwNidaNumber($lnNumber);
                        $sense->setStrongCode($sn);
                        $isNew = true;
                    }
                    $needsUpdate = true;
                } else {
                    // New Skeleton
                    $sense = new LouwNidaSense();
                    $sense->setLouwNidaNumber($lnNumber);
                    $sense->setStrongCode($sn);
                    $this->entityManager->persist($sense);
                    $isNew = true;
                    if ($withGpt) {
                        $needsUpdate = true;
                    }
                    // Add to map so subsequent duplicates are skipped in this run
                    $senseMap[$key] = ['hasData' => $needsUpdate, 'entity' => null];
                }

                // If we need update and have GPT enabled
                if ($needsUpdate && $withGpt) {
                    try {
                        if ($mock) {
                            $data = [
                                'ideia_central_ptbr' => "Ideia central simulada para $sn em $lnNumber",
                                'sentido_semantico_ptbr' => "Sentido semântico simulado para $sn em $lnNumber"
                            ];
                            if (!$dryRun)
                                usleep(100000); // 0.1s
                        } else {
                            $data = $this->fetchGptData($louwNida, $lnNumber, $sn, $apiKey);
                            if (!$dryRun)
                                usleep(500000); // 0.5s
                        }

                        if ($data) {
                            $sense->setIdeiaCentralPtBr($data['ideia_central_ptbr'] ?? null);
                            $sense->setSentidoSemanticoPtBr($data['sentido_semantico_ptbr'] ?? null);

                            if (!$isNew) {
                                $updated++;
                            }
                            // Update map status
                            $senseMap[$key]['hasData'] = true;
                        } else {
                            $errors++;
                        }

                    } catch (\Exception $e) {
                        $errors++;
                        if ($io->isVerbose()) {
                            $io->warning("Error processing LN=$lnNumber SN=$sn: " . $e->getMessage());
                        }
                    }
                }

                if ($isNew) {
                    $created++;
                }
            } // end foreach parts

            $processed++;

            // Flush batch
            if (($processed % 50) === 0 && !$dryRun) {
                $this->entityManager->flush();
                $this->entityManager->clear(); // Clear entities
                // Note: Clearing entities here detaches $sense if we just persisted it. 
                // That's fine for bulk insert.
            }
            $progressBar->advance();
        }

        if (!$dryRun) {
            $this->entityManager->flush();
        }

        $progressBar->finish();
        $io->newLine();

        $io->success(sprintf(
            'Finished. Processed: %d LouwNida records. Created: %d. Updated: %d. Skipped: %d. Errors: %d.',
            $processed,
            $created,
            $updated,
            $skipped,
            $errors
        ));

        return Command::SUCCESS;
    }

    private function fetchGptData(\App\Entity\LouwNida $louwNida, string $lnNumber, string $sn, string $apiKey): ?array
    {
        // Prepare context variables
        $ogntSort = $louwNida->getOgntSort();
        $book = $louwNida->getBook();
        $chapter = $louwNida->getChapter();
        $verse = $louwNida->getVerse();
        $lexeme = $louwNida->getLexeme();
        $rmac = $louwNida->getRmac();
        $glossEn = $louwNida->getTbesg() ?? $louwNida->getTransSbl(); // Fallback
        $glossEs = $louwNida->getEspanol();
        $glossIt = $louwNida->getIt();

        // Construct Prompt
        $prompt = <<<EOT
Você é um lexicógrafo bíblico especializado em grego koiné e domínios semânticos Louw–Nida.
Gere descrições curtas e úteis em português do Brasil.
Siga estritamente o domínio LN fornecido. Não invente fatos nem cite versículos.
Responda SOMENTE com JSON válido no formato solicitado.
Se a informação for insuficiente, use strings vazias.

Tarefa:
A partir do registro abaixo (um token do NT grego), preencha DOIS campos em PT-BR:

1) ideia_central_ptbr:
- 1 frase curta (até 160 caracteres)
- definição do “conceito nuclear” do domínio LN aplicado a este lema

2) sentido_semantico_ptbr:
- 1 a 2 frases (até 260 caracteres)
- explicação um pouco mais precisa, deixando claro “o que é” e “o que não é”, quando fizer sentido

Regras:
- Baseie-se prioritariamente em LN-LouwNidaNumbers.
- Use lexeme, Strong (sn) e as glosas (EN/ES/IT) apenas para desambiguar.
- Não use linguagem teológica interpretativa (ex.: “Jesus é…”), apenas definição lexical/semântica.
- Não inclua aspas, referências, nem exemplos de versículos.
- Responda apenas com JSON com estas chaves:
  {"ideia_central_ptbr":"...","sentido_semantico_ptbr":"..."}

Registro:
OGNTsort: {$ogntSort}
Book: {$book}  Chapter: {$chapter}  Verse: {$verse}
lexeme: {$lexeme}
sn (Strong): {$sn}
rmac: {$rmac}
LN-LouwNidaNumbers: {$lnNumber}
gloss_en: {$glossEn}
gloss_es: {$glossEs}
gloss_it: {$glossIt}
EOT;

        $response = $this->client->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-4o-mini', // Efficient model
                'messages' => [
                    ['role' => 'system', 'content' => 'Você é um lexicógrafo bíblico especializado em grego koiné e domínios semânticos Louw–Nida.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.3,
                'response_format' => ['type' => 'json_object']
            ],
            'timeout' => 30
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('OpenAI API Error: ' . $response->getStatusCode());
        }

        $content = $response->toArray();
        $rawJson = $content['choices'][0]['message']['content'] ?? '{}';

        return json_decode($rawJson, true);
    }
}
