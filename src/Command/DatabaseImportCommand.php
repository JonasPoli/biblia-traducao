<?php
declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Doctrine\DBAL\Connection;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:database:import',
    description: 'Imports the database from a SQL file located in the sql/ directory.'
)]
class DatabaseImportCommand extends Command
{
    public function __construct(
        private Connection $connection,
        private ParameterBagInterface $params
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::OPTIONAL, 'Path to SQL file relative to sql/ directory', 'latest.sql')
            ->addArgument('database', InputArgument::OPTIONAL, 'Target database name (defaults to connection config)', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Get database connection parameters
        $dbParams = $this->connection->getParams();
        $dbName = $dbParams['dbname'] ?? 'app';
        $user = $dbParams['user'] ?? 'root';
        $password = $dbParams['password'] ?? '';
        $host = $dbParams['host'] ?? '127.0.0.1';
        $port = $dbParams['port'] ?? 3306;
        $driver = $dbParams['driver'] ?? 'pdo_mysql';
        $database = $input->getArgument('database') ?? $dbName;

        $sqlDir = $this->params->get('kernel.project_dir') . '/sql';
        $fileArg = $input->getArgument('file');
        $sqlFile = $sqlDir . '/' . $fileArg;

        if (!file_exists($sqlFile)) {
            $io->error("SQL file not found at $sqlFile");
            return Command::FAILURE;
        }

        // Ensure the target database exists; create it if it does not.
        if (str_contains($driver, 'mysql')) {
            $createDbCmd = sprintf(
                "mysql -u%s -p%s -h%s -P%d -e 'CREATE DATABASE IF NOT EXISTS `%s`'",
                escapeshellarg($user),
                escapeshellarg($password),
                escapeshellarg($host),
                $port,
                $database
            );
            $processCreate = Process::fromShellCommandline($createDbCmd);
            $processCreate->run();
            if (!$processCreate->isSuccessful()) {
                $io->error('Failed to create MySQL database: ' . $processCreate->getErrorOutput());
                return Command::FAILURE;
            }
        } elseif (str_contains($driver, 'pgsql')) {
            $createDbCmd = sprintf(
                'PGPASSWORD=%s createdb -U %s -h %s -p %d %s',
                escapeshellarg($password),
                escapeshellarg($user),
                escapeshellarg($host),
                $port,
                escapeshellarg($database)
            );
            $processCreate = Process::fromShellCommandline($createDbCmd);
            $processCreate->run();
            if (!$processCreate->isSuccessful()) {
                $io->error('Failed to create PostgreSQL database: ' . $processCreate->getErrorOutput());
                return Command::FAILURE;
            }
        }

        // Build the import command based on driver
        if (str_contains($driver, 'mysql')) {
            // MySQL import, include database name with -D flag
            $cmd = sprintf(
                'mysql -u%s -p%s -h%s -P%d -D%s < %s',
                escapeshellarg($user),
                escapeshellarg($password),
                escapeshellarg($host),
                $port,
                $database,
                escapeshellarg($sqlFile)
            );
        } elseif (str_contains($driver, 'pgsql')) {
            // PostgreSQL import, include database name with -d flag
            $cmd = sprintf(
                'PGPASSWORD=%s psql -U %s -h %s -p %d -d %s -f %s',
                escapeshellarg($password),
                escapeshellarg($user),
                escapeshellarg($host),
                $port,
                escapeshellarg($database),
                escapeshellarg($sqlFile)
            );
        } else {
            $io->error("Driver '$driver' is not supported for automatic import.");
            return Command::FAILURE;
        }

        $io->text('Running import command...');
        $process = Process::fromShellCommandline($cmd);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            $io->error('Import failed!');
            $io->text($process->getErrorOutput());
            return Command::FAILURE;
        }

        $io->success('Database imported successfully from: ' . $sqlFile);
        return Command::SUCCESS;
    }
}
?>
