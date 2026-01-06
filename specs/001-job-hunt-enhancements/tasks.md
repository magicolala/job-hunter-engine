---

description: "Task list template for feature implementation"
---

# Tasks: Multi-Source Job Aggregation & Intelligence

**Input**: Design documents from `/specs/001-job-hunt-enhancements/`
**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Tests**: Tests are REQUIRED for this feature based on the specification.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

- **Single project**: `src/`, `tests/` at repository root
- Paths shown below assume single project

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization and basic structure

- [ ] T001 [P] Create test directories in `tests/Service`, `tests/Command`, `tests/Repository`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [ ] T002 Create application status enum in `src/Enum/ApplicationStatus.php`
- [ ] T003 Update job entity fields in `src/Entity/Job.php`
- [ ] T004 Create profile criteria entity in `src/Entity/ProfileCriteria.php`
- [ ] T005 Create scraping run entity in `src/Entity/ScrapingRun.php`
- [ ] T006 Update job repository filters/dedup/pipeline queries in `src/Repository/JobRepository.php`
- [ ] T007 Create profile criteria repository in `src/Repository/ProfileCriteriaRepository.php`
- [ ] T008 Create scraping run repository in `src/Repository/ScrapingRunRepository.php`
- [ ] T009 Generate Doctrine migration in `migrations/VersionYYYYMMDDHHMMSS.php`

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - Aggregate Jobs from Multiple Sources (Priority: P0) 🎯 MVP

**Goal**: Support multi-source, multi-query scraping with deduplication and run tracking.

**Independent Test**: Run a 3-source x 3-query scrape and confirm all combinations execute, dedupe occurs, and a scraping run record is created.

### Tests for User Story 1

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [ ] T010 [P] [US1] Add deduplication unit tests in `tests/Service/JobDeduplicatorTest.php`
- [ ] T011 [P] [US1] Add multi-source integration tests in `tests/Command/JobHuntCommandTest.php`

### Implementation for User Story 1

- [ ] T012 [P] [US1] Add source registry mapping in `src/Service/SourceRegistry.php`
- [ ] T013 [P] [US1] Add deduplication service in `src/Service/JobDeduplicator.php`
- [ ] T014 [P] [US1] Add scraping run recorder in `src/Service/ScrapingRunRecorder.php`
- [ ] T015 [US1] Update scraper to accept source/query inputs in `src/Service/JobScraper.php`
- [ ] T016 [US1] Orchestrate multi-source runs in `src/Command/JobHuntCommand.php`

**Checkpoint**: User Story 1 fully functional and independently testable

---

## Phase 4: User Story 2 - Intelligent Pagination (Priority: P1)

**Goal**: Paginate through source results safely with limits and backoff.

**Independent Test**: Mock a source with 3 pages and confirm pagination stops only after all pages or limit reached.

### Tests for User Story 2

- [ ] T017 [P] [US2] Add pagination policy tests in `tests/Service/PaginationPolicyTest.php`
- [ ] T018 [P] [US2] Add rate limiter/backoff tests in `tests/Service/RateLimiterTest.php`

### Implementation for User Story 2

- [ ] T019 [P] [US2] Add pagination policy helper in `src/Service/PaginationPolicy.php`
- [ ] T020 [P] [US2] Add rate limiter/backoff helper in `src/Service/RateLimiter.php`
- [ ] T021 [US2] Extend WTTJ API pagination in `src/Service/JobScraper.php`
- [ ] T022 [US2] Extend Remotive pagination in `src/Service/JobScraper.php`
- [ ] T023 [US2] Enforce per-combination limit handling in `src/Service/JobScraper.php`

**Checkpoint**: User Stories 1 and 2 both work independently

---

## Phase 5: User Story 3 - Prioritize Relevant Offers (Priority: P2)

**Goal**: Score offers against profile criteria and store extracted technologies.

**Independent Test**: Provide sample criteria and offers; verify computed scores and extracted technologies match expectations.

### Tests for User Story 3

- [ ] T024 [P] [US3] Add scoring tests in `tests/Service/ScoringServiceTest.php`
- [ ] T025 [P] [US3] Add technology extraction tests in `tests/Service/TechnologyExtractorTest.php`

### Implementation for User Story 3

- [ ] T026 [P] [US3] Add technology dictionary in `src/Service/TechnologyDictionary.php`
- [ ] T027 [P] [US3] Add technology extractor in `src/Service/TechnologyExtractor.php`
- [ ] T028 [P] [US3] Add scoring service in `src/Service/ScoringService.php`
- [ ] T029 [P] [US3] Add profile criteria service in `src/Service/ProfileCriteriaService.php`
- [ ] T030 [US3] Add ingestion pipeline to apply scoring in `src/Service/JobIngestionService.php`
- [ ] T031 [US3] Wire scoring pipeline into command flow in `src/Command/JobHuntCommand.php`

**Checkpoint**: User Stories 1-3 all work independently

---

## Phase 6: User Story 4 - Filter by Technology and Recency (Priority: P3)

**Goal**: Filter stored offers by technology and publish recency (default 30 days).

**Independent Test**: Query with a technology filter and recency window and verify only matching offers are returned.

### Tests for User Story 4

- [ ] T032 [P] [US4] Add repository filter tests in `tests/Repository/JobRepositoryTest.php`

### Implementation for User Story 4

- [ ] T033 [P] [US4] Add filtering methods to repository in `src/Repository/JobRepository.php`
- [ ] T034 [US4] Add list/filter command in `src/Command/JobListCommand.php`
- [ ] T035 [US4] Add CLI options for technology and recency filters in `src/Command/JobListCommand.php`

**Checkpoint**: User Stories 1-4 all work independently

---

## Phase 7: User Story 5 - Track Application Status (Priority: P4)

**Goal**: Track status changes and summarize pipeline counts.

**Independent Test**: Update statuses for multiple jobs and confirm pipeline summary counts.

### Tests for User Story 5

- [ ] T036 [P] [US5] Add status transition tests in `tests/Service/JobStatusServiceTest.php`

### Implementation for User Story 5

- [ ] T037 [P] [US5] Add status service in `src/Service/JobStatusService.php`
- [ ] T038 [US5] Add status update command in `src/Command/JobStatusCommand.php`
- [ ] T039 [US5] Add pipeline summary command in `src/Command/JobPipelineCommand.php`

**Checkpoint**: All user stories independently functional

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

- [ ] T040 [P] Update quickstart instructions in `specs/001-job-hunt-enhancements/quickstart.md`
- [ ] T041 [P] Add command usage notes in `specs/001-job-hunt-enhancements/plan.md`

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3+)**: All depend on Foundational phase completion
  - User stories can then proceed in parallel (if staffed)
  - Or sequentially in priority order (P0 → P1 → P2 → P3 → P4)
- **Polish (Final Phase)**: Depends on all desired user stories being complete

### User Story Dependencies

- **User Story 1 (P0)**: Can start after Foundational (Phase 2) - No dependencies on other stories
- **User Story 2 (P1)**: Can start after Foundational (Phase 2) - Integrates with US1 scraping pipeline
- **User Story 3 (P2)**: Can start after Foundational (Phase 2) - Integrates with US1 ingestion pipeline
- **User Story 4 (P3)**: Can start after Foundational (Phase 2) - Requires US1 data ingestion
- **User Story 5 (P4)**: Can start after Foundational (Phase 2) - Requires Job status fields

### Within Each User Story

- Tests MUST be written and FAIL before implementation
- Models before services
- Services before commands
- Core implementation before integration
- Story complete before moving to next priority

### Parallel Opportunities

- Setup task T001 can run in parallel
- Foundational tasks T002-T008 can run in parallel by file
- Once Foundational phase completes, all user stories can start in parallel (if team capacity allows)
- All tests for a user story marked [P] can run in parallel
- Services within a story marked [P] can run in parallel
- Different user stories can be worked on in parallel by different team members

---

## Parallel Example: User Story 1

```bash
# Launch all tests for User Story 1 together:
Task: "Add deduplication unit tests in tests/Service/JobDeduplicatorTest.php"
Task: "Add multi-source integration tests in tests/Command/JobHuntCommandTest.php"

# Launch parallel implementation tasks for User Story 1:
Task: "Add source registry mapping in src/Service/SourceRegistry.php"
Task: "Add deduplication service in src/Service/JobDeduplicator.php"
Task: "Add scraping run recorder in src/Service/ScrapingRunRecorder.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (CRITICAL - blocks all stories)
3. Complete Phase 3: User Story 1
4. **STOP and VALIDATE**: Test User Story 1 independently
5. Deploy/demo if ready

### Incremental Delivery

1. Complete Setup + Foundational → Foundation ready
2. Add User Story 1 → Test independently → Deploy/Demo (MVP!)
3. Add User Story 2 → Test independently → Deploy/Demo
4. Add User Story 3 → Test independently → Deploy/Demo
5. Add User Story 4 → Test independently → Deploy/Demo
6. Add User Story 5 → Test independently → Deploy/Demo
7. Each story adds value without breaking previous stories

### Parallel Team Strategy

With multiple developers:

1. Team completes Setup + Foundational together
2. Once Foundational is done:
   - Developer A: User Story 1
   - Developer B: User Story 2
   - Developer C: User Story 3
   - Developer D: User Story 4
   - Developer E: User Story 5
3. Stories complete and integrate independently

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- Each user story should be independently completable and testable
- Verify tests fail before implementing
- Commit after each task or logical group
- Stop at any checkpoint to validate story independently
- Avoid: vague tasks, same file conflicts, cross-story dependencies that break independence
