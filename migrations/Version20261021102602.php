<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261021102602 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add not null for createdAt';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE book SET created_at = NOW() WHERE created_at IS NULL');
        $this->addSql('ALTER TABLE book ALTER COLUMN created_at SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE book ALTER COLUMN created_at DROP NOT NULL');
    }
}
