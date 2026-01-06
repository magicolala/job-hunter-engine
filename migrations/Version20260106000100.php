<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260106000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add job intelligence fields, profile criteria, and scraping run tracking.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE profile_criteria (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, preferred_technologies CLOB NOT NULL, seniority VARCHAR(32) NOT NULL, locations CLOB NOT NULL, remote_only BOOLEAN NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)");
        $this->addSql("CREATE TABLE scraping_run (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, sources CLOB NOT NULL, queries CLOB NOT NULL, total_listings INTEGER NOT NULL DEFAULT 0, unique_listings INTEGER NOT NULL DEFAULT 0, new_jobs INTEGER NOT NULL DEFAULT 0, duplicates INTEGER NOT NULL DEFAULT 0, status VARCHAR(16) NOT NULL DEFAULT 'RUNNING', started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, completed_at DATETIME DEFAULT NULL)");
        $this->addSql("CREATE TABLE job_new (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, external_id VARCHAR(255) UNIQUE DEFAULT NULL, source VARCHAR(64) NOT NULL DEFAULT 'unknown', company VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, published_at DATETIME DEFAULT NULL, technologies CLOB NOT NULL DEFAULT '[]', relevance_score INTEGER DEFAULT NULL, status VARCHAR(32) NOT NULL DEFAULT 'FOUND', status_updated_at DATETIME DEFAULT NULL, contact_name VARCHAR(255) DEFAULT NULL, contact_linkedin VARCHAR(255) DEFAULT NULL, job_url VARCHAR(512) DEFAULT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)");
        $this->addSql("INSERT INTO job_new (id, external_id, source, company, title, contact_name, contact_linkedin, job_url, created_at, updated_at) SELECT id, external_id, 'unknown', company, title, contact_name, contact_linkedin, job_url, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP FROM job");
        $this->addSql('DROP TABLE job');
        $this->addSql('ALTER TABLE job_new RENAME TO job');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE profile_criteria');
        $this->addSql('DROP TABLE scraping_run');
        $this->addSql('CREATE TABLE job_old (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, external_id VARCHAR(255) NOT NULL UNIQUE, company VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, contact_name VARCHAR(255) DEFAULT NULL, contact_linkedin VARCHAR(255) DEFAULT NULL, job_url VARCHAR(512) DEFAULT NULL)');
        $this->addSql("INSERT INTO job_old (id, external_id, company, title, contact_name, contact_linkedin, job_url) SELECT id, COALESCE(external_id, ''), company, title, contact_name, contact_linkedin, job_url FROM job");
        $this->addSql('DROP TABLE job');
        $this->addSql('ALTER TABLE job_old RENAME TO job');
    }
}
