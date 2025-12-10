<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251210120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add numero_arrivee column to courrier table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE courrier ADD numero_arrivee VARCHAR(100) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_NUMERO_ARRIVEE ON courrier (numero_arrivee)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_NUMERO_ARRIVEE');
        $this->addSql('ALTER TABLE courrier DROP numero_arrivee');
    }
}
