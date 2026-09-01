<?php

namespace App\Command;

use App\Service\AuthEmailService;
use App\Service\UserImportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:import',
    description: 'Importa usuários em lote a partir de um arquivo CSV e envia e-mails de definição de senha.',
    aliases: ['app:import-users']
)]
class ImportUsersCommand extends Command
{
    public function __construct(
        private readonly UserImportService $userImportService,
        private readonly AuthEmailService $authEmailService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Caminho para o arquivo CSV com os usuários (ex: var/usuarios.csv)')
            ->addOption('no-email', null, InputOption::VALUE_NONE, 'Não envia e-mails de definição de senha para os usuários')
            ->addOption('overwrite', null, InputOption::VALUE_NONE, 'Atualiza dados de usuários já existentes e reenvia e-mail se solicitado')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filePath = $input->getArgument('file');
        $sendEmail = !$input->getOption('no-email');
        $overwrite = (bool) $input->getOption('overwrite');

        $io->title('Importação em Lote de Usuários');

        if (!file_exists($filePath) || !is_readable($filePath)) {
            $io->error(sprintf('O arquivo "%s" não foi encontrado ou não pode ser lido.', $filePath));
            return Command::FAILURE;
        }

        $csvContent = file_get_contents($filePath);
        if ($csvContent === false || trim($csvContent) === '') {
            $io->error('O arquivo fornecido está vazio.');
            return Command::FAILURE;
        }

        $io->section('Processando arquivo CSV...');
        $rows = $this->userImportService->parseCsv($csvContent);

        if (empty($rows)) {
            $io->warning('Nenhum registro válido encontrado no arquivo CSV.');
            return Command::SUCCESS;
        }

        $io->info(sprintf(
            'Encontrados %d registros no arquivo. Envio de e-mails: %s. Sobrescrever existentes: %s.',
            count($rows),
            $sendEmail ? '<fg=green>SIM</>' : '<fg=yellow>NÃO</>',
            $overwrite ? '<fg=green>SIM</>' : '<fg=yellow>NÃO</>'
        ));

        $progressBar = $io->createProgressBar(count($rows));
        $progressBar->start();

        $result = $this->userImportService->importUsers($rows, $sendEmail, $overwrite);

        $progressBar->finish();
        $io->newLine(2);

        // Display summary table
        $tableRows = [];
        foreach ($result['created'] as $item) {
            $tableRows[] = [
                $item['name'],
                $item['email'],
                $this->authEmailService->getWorkGroupName($item['workGroup']),
                '<fg=green>Criado</>',
                $item['emailSent'] ? '<fg=green>Enviado</>' : ($sendEmail ? '<fg=red>Falhou</>' : '<fg=gray>Ignorado</>'),
            ];
        }

        foreach ($result['updated'] as $item) {
            $tableRows[] = [
                $item['name'],
                $item['email'],
                $this->authEmailService->getWorkGroupName($item['workGroup']),
                '<fg=yellow>Atualizado</>',
                $item['emailSent'] ? '<fg=green>Enviado</>' : ($sendEmail ? '<fg=red>Falhou</>' : '<fg=gray>Ignorado</>'),
            ];
        }

        foreach ($result['skipped'] as $item) {
            $tableRows[] = [
                $item['name'],
                $item['email'],
                '-',
                '<fg=blue>Ignorado (Já existe)</>',
                '-',
            ];
        }

        foreach ($result['errors'] as $item) {
            $tableRows[] = [
                $item['row']['name'] ?? '-',
                $item['row']['email'] ?? '-',
                '-',
                sprintf('<fg=red>Erro: %s</>', $item['message']),
                '-',
            ];
        }

        $table = new Table($output);
        $table->setHeaders(['Nome', 'E-mail', 'Grupo de Trabalho', 'Status', 'E-mail']);
        $table->setRows($tableRows);
        $table->render();

        $io->newLine();
        $io->success(sprintf(
            'Importação concluída! Total: %d | Criados: %d | Atualizados: %d | Ignorados: %d | Erros: %d | E-mails Enviados: %d',
            $result['total'],
            count($result['created']),
            count($result['updated']),
            count($result['skipped']),
            count($result['errors']),
            $result['emails_sent']
        ));

        return Command::SUCCESS;
    }
}
