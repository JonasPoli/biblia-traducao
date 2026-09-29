<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Registra quando o e-mail com o link de senha atual foi enviado (envio passou a ser em segundo plano).
 */
final class Version20260928233000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'User: adiciona password_email_sent_at (data de envio do link de senha atual).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD password_email_sent_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP password_email_sent_at');
    }
}
