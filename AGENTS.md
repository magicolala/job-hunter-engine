# Repository Guidelines

## Project Structure & Module Organization
- `src/` holds application code (entities, services, commands).
- `config/` contains Symfony configuration (packages, services, routes).
- `migrations/` stores Doctrine migration files.
- `public/` is the web root for assets and entry points.
- `var/` is runtime storage (cache, logs, generated files).
- `compose.yaml` and `compose.override.yaml` provide Docker Compose defaults.

## Build, Test, and Development Commands
- `composer install` installs PHP dependencies.
- `php bin/console` lists available Symfony commands.
- `php bin/console doctrine:database:create` creates the configured database.
- `php bin/console doctrine:migrations:diff` generates a migration from entity changes.
- `php bin/console doctrine:migrations:migrate` applies migrations.
- `php bin/console app:hunt` runs the job scraping + lead enrichment + CSV export.

## Coding Style & Naming Conventions
- Follow PSR-12 for PHP; use 4 spaces for indentation.
- Prefer strict types (`declare(strict_types=1);`) and typed properties.
- Classes use `StudlyCaps` and live under `App\` in `src/`.
- Console commands live in `src/Command` and are named `SomethingCommand`.
- Entities live in `src/Entity` and use Doctrine attributes for mapping.

## Testing Guidelines
- No test framework is configured yet. If you add tests, place them in `tests/`
  and use the `App\Tests\` namespace.
- Prefer naming tests `*Test.php` (e.g., `JobHuntCommandTest.php`).

## Commit & Pull Request Guidelines
- No Git history is present, so no commit convention is established.
- Use concise, imperative commit messages (e.g., “Add job scraping service”).
- For PRs, include a short summary, relevant commands run, and any screenshots
  if UI changes are introduced.

## Security & Configuration Tips
- Set secrets in `.env.local` rather than `.env`.
- Required: `APOLLO_API_KEY` for lead enrichment.
- Ensure a Panther driver is available (e.g., `composer require --dev dbrekelmans/bdi`
  then `vendor/bin/bdi detect drivers`) before running `app:hunt`.

## Active Technologies
- PHP 8.2+ + Symfony 7.4 (Console, HttpClient), Doctrine ORM/DBAL (001-job-hunt-enhancements)
- SQLite (Doctrine ORM via DATABASE_URL) (001-job-hunt-enhancements)

## Recent Changes
- 001-job-hunt-enhancements: Added PHP 8.2+ + Symfony 7.4 (Console, HttpClient), Doctrine ORM/DBAL
