<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Múltiplos grupos de trabalho por usuário + controle de convite pendente.
 */
final class Version20260928220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'User: adiciona work_groups (JSON, vários grupos) e password_set_at (convite pendente vs. senha criada); padrão de work_group passa a 1 (não-admin).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD work_groups JSON DEFAULT NULL, ADD password_set_at DATETIME DEFAULT NULL, CHANGE work_group work_group INT DEFAULT 1 NOT NULL');

        // Cada usuário existente fica com o grupo que já tinha
        $this->addSql('UPDATE `user` SET work_groups = JSON_ARRAY(work_group)');

        // Quem não tem token pendente já usa a própria senha. Quem ainda tem token
        // (importados e que nunca abriram o link) continua como "convite pendente".
        $this->addSql('UPDATE `user` SET password_set_at = NOW() WHERE reset_token IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP work_groups, DROP password_set_at, CHANGE work_group work_group INT DEFAULT 0 NOT NULL');
    }
}
