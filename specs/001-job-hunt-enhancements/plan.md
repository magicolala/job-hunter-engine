# Implementation Plan: Multi-Source Job Aggregation & Intelligence

**Branch**: `001-job-hunt-enhancements` | **Date**: 2026-01-06 | **Spec**: `specs/001-job-hunt-enhancements/spec.md`
**Input**: Feature specification from `/specs/001-job-hunt-enhancements/spec.md`

**Note**: This template is filled in by the `/speckit.plan` command. See `.specify/templates/commands/plan.md` for the execution workflow.

## Summary

Deliver multi-source scraping with pagination, deduplication, scoring, technology
extraction, publish-date capture, filtering, and application status tracking.
Implement data model extensions (Job, ProfileCriteria, ScrapingRun), update the
CLI workflow, and add tests for scoring, pagination, and dedupe logic while
preserving current UX and performance expectations.

## Technical Context

**Language/Version**: PHP 8.2+  
**Primary Dependencies**: Symfony 7.4 (Console, HttpClient), Doctrine ORM/DBAL  
**Storage**: SQLite (Doctrine ORM via DATABASE_URL)  
**Testing**: Symfony PHPUnit Bridge (PHPUnit)  
**Target Platform**: CLI on Linux/macOS (Symfony Console)  
**Project Type**: single  
**Performance Goals**: 500 unique listings per run; 3x pagination lift; full run
in under 10 minutes with 3 sources x 3 queries  
**Constraints**: Rate limits from sources; avoid breaking existing CLI UX; keep
memory usage stable for multi-page scraping  
**Scale/Scope**: Single-user CLI with multi-source scraping and tracking

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- Structure matches `src/`, `config/`, `migrations/`, `public/`, `var/` layout or plan documents deviation.
- Strict types + PSR-12 enforcement plan documented for new/changed PHP files.
- Testing expectations recorded; if tests not requested, manual verification noted.
- UX consistency impacts documented (prior behavior vs. new behavior).
- Performance targets recorded with mitigation notes for any regression risk.

**Gate Evaluation (Pre-Research)**: PASS

## Project Structure

### Documentation (this feature)

```text
specs/001-job-hunt-enhancements/
├── plan.md              # This file (/speckit.plan command output)
├── research.md          # Phase 0 output (/speckit.plan command)
├── data-model.md        # Phase 1 output (/speckit.plan command)
├── quickstart.md        # Phase 1 output (/speckit.plan command)
├── contracts/           # Phase 1 output (/speckit.plan command)
└── tasks.md             # Phase 2 output (/speckit.tasks command - NOT created by /speckit.plan)
```

### Source Code (repository root)

```text
src/
├── Command/
├── Entity/
├── Repository/
└── Service/

config/
├── packages/
└── services.yaml

migrations/
public/
var/

tests/
```

**Structure Decision**: Single Symfony console app with Doctrine entities and
services under `src/`. Tests remain under `tests/`.

## Complexity Tracking

No constitution violations identified; no exceptions required.

## Phase 0: Outline & Research

### Research Questions

- Pagination strategy per source and safe stop conditions.
- Deduplication strategy across sources and queries.
- Scoring inputs and weighting (seniority, tech match, location) with 0-100 scale.
- Technology extraction using curated internal list and normalization.
- Publish date parsing and 30-day default recency filter.
- Rate limiting/backoff behavior for multi-page scraping.
- Status update precedence when automation conflicts with manual edits.

### Research Output

`research.md` resolves the above with decisions, rationale, and alternatives.

## Phase 1: Design & Contracts

### Data Model

`data-model.md` defines Job, ProfileCriteria (single-user), and ScrapingRun
fields, validation rules, and status transitions.

### Contracts

`contracts/openapi.yaml` defines the REST interface for jobs, filters, scoring
inputs, and pipeline status, enabling future UI or API clients.

### Quickstart

`quickstart.md` documents local setup, migrations, and how to run the enhanced
scraper with multi-source and pagination options.

### Agent Context Update

Run `/home/ced/projet/job-hunter-engine/.specify/scripts/bash/update-agent-context.sh codex`.

## Constitution Check (Post-Design)

- Code quality: new PHP files use `strict_types=1` and PSR-12 formatting.
- Testing: unit tests for scoring, pagination, and dedupe; integration tests for
  multi-source runs; manual E2E scraping notes due to rate limits.
- UX consistency: new CLI options align with existing command patterns; old
  options remain supported or deprecated with clear messaging.
- Performance: pagination stop conditions and rate limits prevent regressions.

**Gate Evaluation (Post-Design)**: PASS
