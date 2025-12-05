<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251205035432 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE verse_word DROP FOREIGN KEY FK_3D1369E7F7399C73');
        $this->addSql('DROP INDEX IDX_3D1369E7F7399C73 ON verse_word');
        $this->addSql('ALTER TABLE verse_word ADD strong_code VARCHAR(20) DEFAULT NULL, CHANGE strong_code_id strong_definition_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE verse_word ADD CONSTRAINT FK_3D1369E7E8CA1B96 FOREIGN KEY (strong_definition_id) REFERENCES strong_definition (id)');
        $this->addSql('CREATE INDEX IDX_3D1369E7E8CA1B96 ON verse_word (strong_definition_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE verse_word DROP FOREIGN KEY FK_3D1369E7E8CA1B96');
        $this->addSql('DROP INDEX IDX_3D1369E7E8CA1B96 ON verse_word');
        $this->addSql('ALTER TABLE verse_word DROP strong_code, CHANGE strong_definition_id strong_code_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE verse_word ADD CONSTRAINT FK_3D1369E7F7399C73 FOREIGN KEY (strong_code_id) REFERENCES strong_definition (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_3D1369E7F7399C73 ON verse_word (strong_code_id)');
    }
}
