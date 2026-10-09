<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261009103637 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Init database';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE book (id UUID NOT NULL, isbn VARCHAR(13) DEFAULT NULL, published_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, title VARCHAR(255) NOT NULL, author VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CBE5A331CC1CF4E6 ON book (isbn)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE book');
    }
}
