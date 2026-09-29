<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Dados pessoais editáveis no painel "Meu Perfil".
 */
final class Version20260929000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'User: adiciona phone, address, city e state (UF) para o painel Meu Perfil.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD phone VARCHAR(30) DEFAULT NULL, ADD address VARCHAR(255) DEFAULT NULL, ADD city VARCHAR(120) DEFAULT NULL, ADD state VARCHAR(2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP phone, DROP address, DROP city, DROP state');
    }
}
