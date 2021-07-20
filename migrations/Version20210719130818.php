<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210719130818 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE orders_products (id INT AUTO_INCREMENT NOT NULL, parent_order_id INT NOT NULL, product_id INT NOT NULL, qty INT NOT NULL, INDEX IDX_749C879C1252C1E9 (parent_order_id), INDEX IDX_749C879C4584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE orders_products ADD CONSTRAINT FK_749C879C1252C1E9 FOREIGN KEY (parent_order_id) REFERENCES orders (id)');
        $this->addSql('ALTER TABLE orders_products ADD CONSTRAINT FK_749C879C4584665A FOREIGN KEY (product_id) REFERENCES products (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE orders_products');
    }
}
