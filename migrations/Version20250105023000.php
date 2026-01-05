<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250105023000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create job table with job_url column';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE job (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, external_id VARCHAR(255) NOT NULL, company VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, contact_name VARCHAR(255) DEFAULT NULL, contact_linkedin VARCHAR(255) DEFAULT NULL, job_url VARCHAR(512) DEFAULT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FBD8E0F7E5DEB0F8 ON job (external_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE job');
    }
}
