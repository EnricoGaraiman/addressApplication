<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210706084111 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE addresses DROP FOREIGN KEY FK_6FCA75165713BC80');
        $this->addSql('DROP INDEX IDX_6FCA75165713BC80 ON addresses');
        $this->addSql('ALTER TABLE addresses CHANGE addresses_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE addresses ADD CONSTRAINT FK_6FCA7516A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_6FCA7516A76ED395 ON addresses (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE addresses DROP FOREIGN KEY FK_6FCA7516A76ED395');
        $this->addSql('DROP INDEX IDX_6FCA7516A76ED395 ON addresses');
        $this->addSql('ALTER TABLE addresses CHANGE user_id addresses_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE addresses ADD CONSTRAINT FK_6FCA75165713BC80 FOREIGN KEY (addresses_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_6FCA75165713BC80 ON addresses (addresses_id)');
    }
}
