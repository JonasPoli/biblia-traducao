<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260123174952 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE paratext (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, type VARCHAR(50) NOT NULL, chapter INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, book_id INT DEFAULT NULL, author_id INT DEFAULT NULL, INDEX IDX_1CC4834416A2B381 (book_id), INDEX IDX_1CC48344F675F31B (author_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE paratext ADD CONSTRAINT FK_1CC4834416A2B381 FOREIGN KEY (book_id) REFERENCES book (id)');
        $this->addSql('ALTER TABLE paratext ADD CONSTRAINT FK_1CC48344F675F31B FOREIGN KEY (author_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE paratext DROP FOREIGN KEY FK_1CC4834416A2B381');
        $this->addSql('ALTER TABLE paratext DROP FOREIGN KEY FK_1CC48344F675F31B');
        $this->addSql('DROP TABLE paratext');
    }
}
