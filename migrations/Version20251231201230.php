<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add last_activity_checked_at column to utilisateur table
 */
final class Version20251231201230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add last_activity_checked_at column to utilisateur table for tracking admin activity views';
    }

    public function up(Schema $schema): void
    {
        // Check if column exists before adding it
        $this->addSql('ALTER TABLE utilisateur ADD COLUMN IF NOT EXISTS last_activity_checked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN utilisateur.last_activity_checked_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur DROP COLUMN IF EXISTS last_activity_checked_at');
    }
}
