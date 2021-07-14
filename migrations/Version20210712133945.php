<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210712133945 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE addresses ADD city_id INT NOT NULL');
        $this->addSql('ALTER TABLE addresses ADD CONSTRAINT FK_6FCA75168BAC62AF FOREIGN KEY (city_id) REFERENCES city (id)');
        $this->addSql('CREATE INDEX IDX_6FCA75168BAC62AF ON addresses (city_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE addresses DROP FOREIGN KEY FK_6FCA75168BAC62AF');
        $this->addSql('DROP INDEX IDX_6FCA75168BAC62AF ON addresses');
        $this->addSql('ALTER TABLE addresses DROP city_id');
    }
}
