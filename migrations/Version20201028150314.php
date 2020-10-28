<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201028150314 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE pin_tag (pin_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_175BE98E6C3B254C (pin_id), INDEX IDX_175BE98EBAD26311 (tag_id), PRIMARY KEY(pin_id, tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE pin_tag ADD CONSTRAINT FK_175BE98E6C3B254C FOREIGN KEY (pin_id) REFERENCES pins (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE pin_tag ADD CONSTRAINT FK_175BE98EBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE pin_tag');
    }
}
