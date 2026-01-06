# Feature Specification: Multi-Source Job Aggregation & Intelligence

**Feature Branch**: `001-job-hunt-enhancements`  
**Created**: 2026-01-06  
**Updated**: 2026-01-06  
**Status**: Draft  
**Input**: User description: "Augmenter le volume de data avec multi-sources, multi-queries, pagination, scoring, extraction des technologies, dates de publication, filtres geo, et suivi de candidatures."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Aggregate Jobs from Multiple Sources (Priority: P0)

As a job seeker, I want to scrape job offers from multiple sources (WTTJ, Remotive, LinkedIn, Indeed, etc.) with multiple search queries so I can maximize the number of relevant opportunities.

**Why this priority**: More sources and queries directly increase job discovery volume, which is the foundation for all other features.

**Independent Test**: Run the scraper with 3 sources and 3 queries (9 combinations), verify that all combinations are executed and results are deduplicated.

**Acceptance Scenarios**:

1. **Given** multiple sources configured, **When** scraping runs, **Then** each source is queried and results are aggregated.
2. **Given** multiple queries per source, **When** scraping runs, **Then** all query combinations are executed.
3. **Given** duplicate offers across sources, **When** deduplication runs, **Then** only unique offers by externalId are retained.
4. **Given** a failed source, **When** scraping runs, **Then** other sources continue processing and errors are logged.

---

### User Story 2 - Intelligent Pagination (Priority: P1)

As a job seeker, I want the scraper to automatically paginate through all available results for each source/query combination so I don't miss opportunities beyond the first page.

**Why this priority**: Most job boards return 20-50 results per page; pagination can 10x the volume.

**Independent Test**: Mock a source with 150 results across 3 pages, verify all results are fetched.

**Acceptance Scenarios**:

1. **Given** a source with multiple pages, **When** scraping runs without a limit, **Then** all pages are fetched until no more results.
2. **Given** a limit parameter, **When** scraping runs, **Then** pagination stops after reaching the limit.
3. **Given** a source that returns empty results, **When** scraping runs, **Then** pagination stops gracefully.

---

### User Story 3 - Prioritize Relevant Offers (Priority: P2)

As a job seeker, I want each offer to be scored against my profile criteria so I can focus on the most relevant opportunities first.

**Why this priority**: Prioritization reduces time spent on low-fit offers and increases application quality.

**Independent Test**: Import a set of offers with known criteria and confirm the system produces a ranked list that matches expected relevance ordering.

**Acceptance Scenarios**:

1. **Given** offers with varied seniority, location, and technologies, **When** scoring runs, **Then** each offer has a score and the highest-fit offers are ranked at the top.
2. **Given** a change to the user profile criteria, **When** scores are recalculated, **Then** the ranking updates to reflect the new preferences.

---

### User Story 4 - Filter by Technology and Recency (Priority: P3)

As a job seeker, I want to filter offers by extracted technologies and publish recency so I can target up-to-date roles that match my stack.

**Why this priority**: Filtering by stack and freshness improves relevance and response rates.

**Independent Test**: Load offers with mixed technologies and dates; verify that filters return only matching, recent offers.

**Acceptance Scenarios**:

1. **Given** offers with extracted technologies, **When** I filter by a technology, **Then** only offers containing that technology are shown.
2. **Given** offers with publish dates, **When** I filter by recency window, **Then** only offers within that window are shown.

---

### User Story 5 - Track Application Status (Priority: P4)

As a job seeker, I want to track the status of each application so I can monitor my pipeline and follow up efficiently.

**Why this priority**: Status tracking ensures clarity on progress and prevents missed follow-ups.

**Independent Test**: Update statuses for a set of offers and confirm the system persists and reports the correct pipeline counts.

**Acceptance Scenarios**:

1. **Given** a new offer, **When** I set its status to CONTACTED, **Then** the offer reflects the new status in the pipeline view.
2. **Given** multiple offers with statuses, **When** I view the pipeline summary, **Then** counts match the stored statuses.

---

### Edge Cases

- Offers without a clear publish date are labeled as undated and excluded from recency filters by default.
- Offers with missing or ambiguous location data are treated as unknown and only included when no location filter is applied.
- Duplicate offers with different externalIds from different sources are kept as separate entries.
- Sources that fail to respond or timeout should not block other sources from completing.
- Pagination on sources with rate limits should implement exponential backoff.

## Requirements *(mandatory)*

### Functional Requirements

#### Multi-Source Aggregation
- **FR-001**: System MUST support multiple scraping sources via command option `--sources` (array).
- **FR-002**: System MUST support multiple search queries via command option `--queries` (array).
- **FR-003**: System MUST execute all source/query combinations (cartesian product).
- **FR-004**: System MUST deduplicate offers based on `externalId` before persistence.
- **FR-005**: System MUST log the number of combinations, total listings, and unique listings.
- **FR-006**: System MUST handle source failures gracefully without blocking other sources.

#### Pagination
- **FR-007**: System MUST implement automatic pagination for sources that support it (WTTJ API, Remotive, etc.).
- **FR-008**: System MUST respect the `--limit` parameter as a per-combination limit, not global.
- **FR-009**: System MUST stop pagination when a source returns empty results or fewer than expected.
- **FR-010**: System SHOULD implement rate limiting and backoff for paginated requests.

#### New Data Sources
- **FR-011**: System SHOULD support LinkedIn Jobs scraping (stretch goal).
- **FR-012**: System SHOULD support Indeed API integration (stretch goal).
- **FR-013**: System SHOULD support French job boards (alsacreations, humancoders, lesjeudis) (stretch goal).

#### Intelligence & Scoring
- **FR-014**: System MUST store user profile criteria used for scoring (role seniority, preferred technologies, location preferences).
- **FR-015**: System MUST compute a relevance score for each offer based on the stored profile criteria.
- **FR-016**: System MUST extract and store technologies mentioned in offer descriptions.
- **FR-017**: System MUST capture and store offer publish dates when available.

#### Filtering & Tracking
- **FR-018**: Users MUST be able to filter offers by extracted technologies.
- **FR-019**: Users MUST be able to filter offers by publish recency.
- **FR-020**: Users MUST be able to set and update application status for each offer (FOUND, CONTACTED, INTERVIEWING, REJECTED, ACCEPTED).
- **FR-021**: System MUST present a pipeline summary showing counts per status.

### Key Entities *(include if feature involves data)*

- **Job**: Existing entity, enhanced with:
  - `source` (string): Source identifier (wttj-api, remotive, linkedin, etc.)
  - `description` (text, nullable): Full job description for technology extraction
  - `publishedAt` (datetime, nullable): Publish date
  - `location` (string, nullable): Job location
  - `technologies` (json): Array of extracted technologies
  - `relevanceScore` (float, nullable): Computed relevance score
  - `status` (enum): FOUND, CONTACTED, INTERVIEWING, REJECTED, ACCEPTED
  - `statusUpdatedAt` (datetime, nullable): Last status change timestamp

- **ProfileCriteria**: New entity for user preferences:
  - `userId` (string): User identifier (for multi-user support)
  - `preferredTechnologies` (json): Array of preferred tech stack
  - `seniority` (string): junior, mid, senior, lead
  - `locations` (json): Array of acceptable locations/regions
  - `remoteOnly` (boolean): Filter for remote positions
  - `createdAt` (datetime)
  - `updatedAt` (datetime)

- **ScrapingRun**: New entity for audit trail:
  - `sources` (json): Array of sources used
  - `queries` (json): Array of queries used
  - `totalListings` (int): Total listings scraped
  - `uniqueListings` (int): Unique listings after deduplication
  - `newJobs` (int): New jobs created
  - `duplicates` (int): Duplicates skipped
  - `startedAt` (datetime)
  - `completedAt` (datetime, nullable)
  - `status` (enum): RUNNING, COMPLETED, FAILED

### Assumptions

- This feature focuses on multi-source aggregation, pagination, offer scoring, technology extraction, publish date capture, filtering, and application status tracking.
- Initial implementation will support existing sources (WTTJ, Remotive) with enhanced pagination.
- New sources (LinkedIn, Indeed, French boards) are stretch goals for future iterations.
- Alerts, ATS exports, salary analytics, red-flag detection, enrichment caching, and message generation remain out of scope.

## Success Criteria *(mandatory)*

### Measurable Outcomes

#### Volume Metrics
- **SC-001**: System scrapes at least 500 unique job listings per run with default sources and 3 queries.
- **SC-002**: Pagination fetches at least 3x more results than single-page scraping for paginated sources.
- **SC-003**: Deduplication reduces total listings by 10-30% (indicating effective multi-source coverage).

#### Quality Metrics
- **SC-004**: 90% of imported offers receive a relevance score and extracted technologies.
- **SC-005**: Users can shortlist relevant offers in under 5 minutes for a dataset of 100 offers.
- **SC-006**: 95% of applications have an explicit status assigned within 24 hours of being marked as CONTACTED.
- **SC-007**: At least 80% of users report that filtering by technology and recency reduces time spent reviewing irrelevant offers.

#### Performance Metrics
- **SC-008**: Full scraping run with 3 sources × 3 queries completes in under 10 minutes without enrichment.
- **SC-009**: Scraping with enrichment maintains throughput of at least 1 offer/second with 1000ms throttle.

## Constitution Check

### Compliance Review

- ✅ **Code Quality**: All new code will use `strict_types=1`, typed properties, and PSR-12 formatting.
- ✅ **Testing**: Unit tests will cover `deduplicateListings()`, pagination logic, and scoring algorithms. Integration tests will verify multi-source execution.
- ✅ **UX Consistency**: Command interface maintains existing patterns, adding new array options `--sources` and `--queries` that feel natural alongside existing options.
- ✅ **Performance**: Pagination and multi-source execution are designed to be I/O-bound, not CPU-bound. Rate limiting and throttling prevent API abuse.

### Justified Exceptions

- **Manual Testing for Scraping**: Full end-to-end scraping tests against live sources are impractical due to API rate limits and changing data. Manual verification will be documented with screenshots and logs.
- **Backward Compatibility**: The `--url` and `--source` options are deprecated but retained for backward compatibility. Migration guide will be provided in documentation.

---

**Next Steps**: Review this spec, then proceed to implementation plan with database migrations, service layer updates, and command enhancements.