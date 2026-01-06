# Quickstart: Multi-Source Job Aggregation & Intelligence

## Prerequisites

- PHP 8.2+
- Composer
- SQLite (default via DATABASE_URL)

## Setup

```bash
composer install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

## Configure Sources

Set API keys in `.env.local` as needed for sources and enrichment.

## Run Scraper (Planned Options)

```bash
php bin/console app:hunt --sources=wttj,remotive --queries="symfony,php" --limit=100
```

## Verify Results

- Confirm new jobs are persisted.
- Confirm deduplication reduces duplicates.
- Confirm scores, technologies, and published dates are populated.
- Confirm status updates and pipeline summary reflect changes.

## Manual Checks (Due to Live Source Variability)

- Run with at least two sources and two queries and inspect logs for failures.
- Verify pagination stops when results are exhausted.
