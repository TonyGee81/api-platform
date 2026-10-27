<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261021100759 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Book: Add new properties';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE book ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE book ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE book DROP created_at');
        $this->addSql('ALTER TABLE book DROP updated_at');
    }
}
