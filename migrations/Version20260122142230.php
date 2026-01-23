<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260122142230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE louw_nida (id INT AUTO_INCREMENT NOT NULL, ognt_sort INT NOT NULL, tantt_sort INT DEFAULT NULL, features_sort INT DEFAULT NULL, levinsohn_clause_id VARCHAR(255) DEFAULT NULL, ot_quotation VARCHAR(255) DEFAULT NULL, bgb_sort INT DEFAULT NULL, lt_sort INT DEFAULT NULL, st_sort INT DEFAULT NULL, book INT NOT NULL, chapter INT NOT NULL, verse INT NOT NULL, ognt_k VARCHAR(255) DEFAULT NULL, ognt_u LONGTEXT DEFAULT NULL, ognt_a VARCHAR(255) DEFAULT NULL, lexeme VARCHAR(255) DEFAULT NULL, rmac VARCHAR(255) DEFAULT NULL, sn VARCHAR(255) DEFAULT NULL, bdag_entry VARCHAR(255) DEFAULT NULL, ednt_entry VARCHAR(255) DEFAULT NULL, mounce_entry VARCHAR(255) DEFAULT NULL, gk_number VARCHAR(255) DEFAULT NULL, ln_number VARCHAR(255) DEFAULT NULL, trans_sbl_cap VARCHAR(255) DEFAULT NULL, trans_sbl VARCHAR(255) DEFAULT NULL, modern_greek VARCHAR(255) DEFAULT NULL, phonetic VARCHAR(255) DEFAULT NULL, tbesg LONGTEXT DEFAULT NULL, it LONGTEXT DEFAULT NULL, lt LONGTEXT DEFAULT NULL, st LONGTEXT DEFAULT NULL, espanol LONGTEXT DEFAULT NULL, pmp_word VARCHAR(255) DEFAULT NULL, pmf_word VARCHAR(255) DEFAULT NULL, note LONGTEXT DEFAULT NULL, louw_nida_domain_id INT DEFAULT NULL, INDEX IDX_E07AC114E30AB22E (louw_nida_domain_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE louw_nida_domain (id INT AUTO_INCREMENT NOT NULL, domain_number INT NOT NULL, subdomain_number INT NOT NULL, category VARCHAR(255) NOT NULL, semantic_domain_id VARCHAR(50) NOT NULL, greek_example LONGTEXT DEFAULT NULL, gloss_english LONGTEXT DEFAULT NULL, gloss_portuguese LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE louw_nida ADD CONSTRAINT FK_E07AC114E30AB22E FOREIGN KEY (louw_nida_domain_id) REFERENCES louw_nida_domain (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE louw_nida DROP FOREIGN KEY FK_E07AC114E30AB22E');
        $this->addSql('DROP TABLE louw_nida');
        $this->addSql('DROP TABLE louw_nida_domain');
    }
}
