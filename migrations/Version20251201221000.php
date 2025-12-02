<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251201221000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bible_version (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, abbreviation VARCHAR(20) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE book (id INT AUTO_INCREMENT NOT NULL, testament_id INT NOT NULL, name VARCHAR(255) NOT NULL, abbreviation VARCHAR(10) NOT NULL, book_order INT NOT NULL, INDEX IDX_CBE5A331386D1BF0 (testament_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE global_reference (id INT AUTO_INCREMENT NOT NULL, term VARCHAR(255) NOT NULL, reference_text LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE strong_definition (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(20) NOT NULL, hebrew_word VARCHAR(255) DEFAULT NULL, greek_word VARCHAR(255) DEFAULT NULL, transliteration VARCHAR(255) DEFAULT NULL, full_definition LONGTEXT DEFAULT NULL, definition LONGTEXT DEFAULT NULL, lemma VARCHAR(255) DEFAULT NULL, pronunciation VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE testament (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE translation_history (id INT AUTO_INCREMENT NOT NULL, verse_text_id INT NOT NULL, user_id INT NOT NULL, old_text LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_86B689B970B7E4DD (verse_text_id), INDEX IDX_86B689B9A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE verse (id INT AUTO_INCREMENT NOT NULL, book_id INT NOT NULL, chapter INT NOT NULL, verse INT NOT NULL, INDEX IDX_D2F7E69F16A2B381 (book_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE verse_reference (id INT AUTO_INCREMENT NOT NULL, verse_id INT NOT NULL, term VARCHAR(255) NOT NULL, reference_text LONGTEXT NOT NULL, INDEX IDX_5341CC34BBF309FA (verse_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE verse_text (id INT AUTO_INCREMENT NOT NULL, verse_id INT NOT NULL, version_id INT NOT NULL, user_id INT DEFAULT NULL, text LONGTEXT NOT NULL, title VARCHAR(255) DEFAULT NULL, INDEX IDX_C569BB31BBF309FA (verse_id), INDEX IDX_C569BB314BBC2705 (version_id), INDEX IDX_C569BB31A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE verse_word (id INT AUTO_INCREMENT NOT NULL, verse_id INT NOT NULL, strong_code_id INT DEFAULT NULL, word_original VARCHAR(255) DEFAULT NULL, word_portuguese VARCHAR(255) DEFAULT NULL, transliteration VARCHAR(255) DEFAULT NULL, english_type VARCHAR(255) DEFAULT NULL, portuguese_type VARCHAR(255) DEFAULT NULL, position INT NOT NULL, INDEX IDX_3D1369E7BBF309FA (verse_id), INDEX IDX_3D1369E7F7399C73 (strong_code_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE book ADD CONSTRAINT FK_CBE5A331386D1BF0 FOREIGN KEY (testament_id) REFERENCES testament (id)');
        $this->addSql('ALTER TABLE translation_history ADD CONSTRAINT FK_86B689B970B7E4DD FOREIGN KEY (verse_text_id) REFERENCES verse_text (id)');
        $this->addSql('ALTER TABLE translation_history ADD CONSTRAINT FK_86B689B9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE verse ADD CONSTRAINT FK_D2F7E69F16A2B381 FOREIGN KEY (book_id) REFERENCES book (id)');
        $this->addSql('ALTER TABLE verse_reference ADD CONSTRAINT FK_5341CC34BBF309FA FOREIGN KEY (verse_id) REFERENCES verse (id)');
        $this->addSql('ALTER TABLE verse_text ADD CONSTRAINT FK_C569BB31BBF309FA FOREIGN KEY (verse_id) REFERENCES verse (id)');
        $this->addSql('ALTER TABLE verse_text ADD CONSTRAINT FK_C569BB314BBC2705 FOREIGN KEY (version_id) REFERENCES bible_version (id)');
        $this->addSql('ALTER TABLE verse_text ADD CONSTRAINT FK_C569BB31A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE verse_word ADD CONSTRAINT FK_3D1369E7BBF309FA FOREIGN KEY (verse_id) REFERENCES verse (id)');
        $this->addSql('ALTER TABLE verse_word ADD CONSTRAINT FK_3D1369E7F7399C73 FOREIGN KEY (strong_code_id) REFERENCES strong_definition (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book DROP FOREIGN KEY FK_CBE5A331386D1BF0');
        $this->addSql('ALTER TABLE translation_history DROP FOREIGN KEY FK_86B689B970B7E4DD');
        $this->addSql('ALTER TABLE translation_history DROP FOREIGN KEY FK_86B689B9A76ED395');
        $this->addSql('ALTER TABLE verse DROP FOREIGN KEY FK_D2F7E69F16A2B381');
        $this->addSql('ALTER TABLE verse_reference DROP FOREIGN KEY FK_5341CC34BBF309FA');
        $this->addSql('ALTER TABLE verse_text DROP FOREIGN KEY FK_C569BB31BBF309FA');
        $this->addSql('ALTER TABLE verse_text DROP FOREIGN KEY FK_C569BB314BBC2705');
        $this->addSql('ALTER TABLE verse_text DROP FOREIGN KEY FK_C569BB31A76ED395');
        $this->addSql('ALTER TABLE verse_word DROP FOREIGN KEY FK_3D1369E7BBF309FA');
        $this->addSql('ALTER TABLE verse_word DROP FOREIGN KEY FK_3D1369E7F7399C73');
        $this->addSql('DROP TABLE bible_version');
        $this->addSql('DROP TABLE book');
        $this->addSql('DROP TABLE global_reference');
        $this->addSql('DROP TABLE strong_definition');
        $this->addSql('DROP TABLE testament');
        $this->addSql('DROP TABLE translation_history');
        $this->addSql('DROP TABLE verse');
        $this->addSql('DROP TABLE verse_reference');
        $this->addSql('DROP TABLE verse_text');
        $this->addSql('DROP TABLE verse_word');
    }
}
