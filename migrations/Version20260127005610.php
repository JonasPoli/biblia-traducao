<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260127005610 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE paratext_review (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, paratext_id INT NOT NULL, INDEX IDX_AE4AF765A76ED395 (user_id), INDEX IDX_AE4AF7659D752D11 (paratext_id), UNIQUE INDEX unique_user_paratext_review (user_id, paratext_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE verse_review (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, verse_id INT NOT NULL, INDEX IDX_2FA46550A76ED395 (user_id), INDEX IDX_2FA46550BBF309FA (verse_id), UNIQUE INDEX unique_user_verse_review (user_id, verse_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE paratext_review ADD CONSTRAINT FK_AE4AF765A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE paratext_review ADD CONSTRAINT FK_AE4AF7659D752D11 FOREIGN KEY (paratext_id) REFERENCES paratext (id)');
        $this->addSql('ALTER TABLE verse_review ADD CONSTRAINT FK_2FA46550A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE verse_review ADD CONSTRAINT FK_2FA46550BBF309FA FOREIGN KEY (verse_id) REFERENCES verse (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE paratext_review DROP FOREIGN KEY FK_AE4AF765A76ED395');
        $this->addSql('ALTER TABLE paratext_review DROP FOREIGN KEY FK_AE4AF7659D752D11');
        $this->addSql('ALTER TABLE verse_review DROP FOREIGN KEY FK_2FA46550A76ED395');
        $this->addSql('ALTER TABLE verse_review DROP FOREIGN KEY FK_2FA46550BBF309FA');
        $this->addSql('DROP TABLE paratext_review');
        $this->addSql('DROP TABLE verse_review');
    }
}
