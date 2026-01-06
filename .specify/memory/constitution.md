<!--
Sync Impact Report:
- Version change: 0.1.1 -> 1.0.0
- Modified principles:
  - I. Structure Is Canon -> I. Code Quality Baseline
  - II. Strict Types and PSR-12 -> II. Testing Standards
  - III. Symfony/Doctrine Conventions -> III. User Experience Consistency
  - IV. Spec-Driven Testing -> IV. Performance Requirements
  - V. Secrets Stay Local -> Removed
- Added sections: None
- Removed sections: None
- Templates requiring updates:
  - ✅ .specify/templates/plan-template.md
  - ✅ .specify/templates/spec-template.md (no change required)
  - ✅ .specify/templates/tasks-template.md (no change required)
- Follow-up TODOs: None
-->
# Job Hunter Engine Constitution

## Core Principles

### I. Code Quality Baseline
All production PHP in `src/` and `tests/` MUST declare `strict_types=1`, use typed
properties where feasible, and follow PSR-12 formatting with 4-space indentation.
Project structure MUST remain consistent with `src/`, `config/`, `migrations/`,
`public/`, and `var/` unless the plan justifies a new top-level directory.
Rationale: a consistent, strict baseline reduces defects and review friction.

### II. Testing Standards
When a change impacts behavior, a test MUST be added or updated in `tests/` using
the `App\Tests\` namespace and `*Test.php` filenames. If the feature spec declines
tests, the plan MUST document manual verification steps and sign-off criteria.
Rationale: test coverage tracks user-impacting changes, while manual checks are
explicitly owned when tests are out of scope.

### III. User Experience Consistency
User-facing flows MUST preserve existing interaction patterns, naming, and data
presentation formats unless the spec explicitly calls for a change. Any UI/UX
change MUST document the prior behavior and the intended new behavior to ensure
reviewers can validate consistency. Rationale: predictable experiences reduce
user confusion and support overhead.

### IV. Performance Requirements
Performance-sensitive changes MUST document expected latency, throughput, or
resource targets in the plan. Regressions in existing performance metrics are
not acceptable without explicit approval and mitigation steps. Rationale:
performance constraints protect the core workflow from slowdowns.

## Development Workflow

- Install dependencies with `composer install` before running application commands.
- Use `php bin/console` for Symfony operations, including
  `doctrine:database:create`, `doctrine:migrations:diff`, and
  `doctrine:migrations:migrate`.
- Run `php bin/console app:hunt` for job scraping, enrichment, and CSV export.

## Operational Requirements

- Ensure a Panther driver is available before running `app:hunt` (e.g.,
  `composer require --dev dbrekelmans/bdi` then `vendor/bin/bdi detect drivers`).
- Store environment-specific configuration in `.env.local`, not `.env`.
- Treat `compose.yaml` and `compose.override.yaml` as the default container
  runtime configuration.

## Governance

- This constitution supersedes other project practices and templates.
- Amendments require updating this file, recording the Sync Impact Report, and
  bumping the semantic version according to the change scope.
- Versioning uses MAJOR for breaking governance changes, MINOR for new principles
  or sections, and PATCH for clarifications or wording-only edits.
- Every spec/plan/tasks document MUST include a Constitution Check and note any
  justified exceptions; reviewers MUST block changes without documented approval.
- Runtime development guidance lives in `AGENTS.md` and must stay consistent with
  these principles.

**Version**: 1.0.0 | **Ratified**: 2026-01-06 | **Last Amended**: 2026-01-06
