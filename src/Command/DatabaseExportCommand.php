<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:database:export',
    description: 'Exports the database to a SQL file in the sql/ directory.',
)]
class DatabaseExportCommand extends Command
{
    public function __construct(
        private Connection $connection,
        private ParameterBagInterface $params
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Get database connection parameters
        $params = $this->connection->getParams();

        // Extract parameters safely, providing defaults where appropriate
        $dbName = $params['dbname'] ?? 'app';
        $user = $params['user'] ?? 'root';
        $password = $params['password'] ?? '';
        $host = $params['host'] ?? '127.0.0.1';
        $port = $params['port'] ?? 3306;
        $driver = $params['driver'] ?? 'pdo_mysql';

        // Define export directory
        $projectDir = $this->params->get('kernel.project_dir');
        $exportDir = $projectDir . '/sql';

        if (!is_dir($exportDir)) {
            if (!mkdir($exportDir, 0755, true) && !is_dir($exportDir)) {
                $io->error(sprintf('Directory "%s" was not created', $exportDir));
                return Command::FAILURE;
            }
        }

        // Generate filename with timestamp
        $timestamp = date('Y-m-d_H-i-s');
        $filename = sprintf('%s/%s_%s.sql', $exportDir, $dbName, $timestamp);

        $io->section('Database Export');
        $io->text(sprintf('Database: <info>%s</info>', $dbName));
        $io->text(sprintf('Host: <info>%s</info>', $host));
        $io->text(sprintf('Driver: <info>%s</info>', $driver));

        $cmd = '';
        $env = [];

        if (str_contains($driver, 'mysql')) {
            // MySQL / MariaDB
            $env = ['MYSQL_PWD' => $password];
            // Added flags:
            // --routines: Export stored procedures and functions
            // --triggers: Export triggers
            // --events: Export events
            // --hex-blob: Dump binary strings in hexadecimal format
            // --single-transaction: Consistent snapshot (InnoDB)
            // --add-drop-table: Add DROP TABLE before CREATE TABLE
            // --complete-insert: Use complete INSERT statements that include column names
            $cmd = sprintf(
                'mysqldump -h %s -P %s -u %s --routines --triggers --events --hex-blob --single-transaction --add-drop-table --complete-insert %s > "%s"',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($user),
                escapeshellarg($dbName),
                $filename
            );
        } elseif (str_contains($driver, 'pgsql') || str_contains($driver, 'postgres')) {
            // PostgreSQL
            $env = ['PGPASSWORD' => $password];
            // Added flags:
            // --clean: Clean (drop) database objects before recreating
            // --if-exists: Use IF EXISTS when dropping objects
            $cmd = sprintf(
                'pg_dump -h %s -p %s -U %s -F p -b -v --clean --if-exists -f "%s" %s',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($user),
                $filename,
                escapeshellarg($dbName)
            );
        } else {
            $io->error(sprintf('Driver "%s" is not supported for automatic export.', $driver));
            return Command::FAILURE;
        }

        $io->text('Running export command...');

        // Run the command
        $process = Process::fromShellCommandline($cmd, null, $env);
        $process->setTimeout(300); // 5 minutes timeout
        $process->run();

        if (!$process->isSuccessful()) {
            $io->error('Export failed!');
            $io->text($process->getErrorOutput());
            return Command::FAILURE;
        }

        $io->success(sprintf('Database exported successfully to: %s', $filename));

        return Command::SUCCESS;
    }
}
