# Data Model: Multi-Source Job Aggregation & Intelligence

## Job

**Description**: Existing entity enhanced to support multi-source, scoring, and
tracking.

**Fields**:
- id (uuid or int)
- source (string, required)
- externalId (string, nullable)
- title (string, required)
- company (string, required)
- location (string, nullable)
- description (text, nullable)
- publishedAt (datetime, nullable)
- technologies (json array, default empty)
- relevanceScore (int, nullable, 0-100)
- status (enum: FOUND, CONTACTED, INTERVIEWING, REJECTED, ACCEPTED; default FOUND)
- statusUpdatedAt (datetime, nullable)
- createdAt (datetime)
- updatedAt (datetime)

**Validation Rules**:
- source, title, company required.
- technologies must be normalized canonical values.
- relevanceScore must be between 0 and 100.

**Relationships**:
- Many Job records per ScrapingRun (optional foreign key).

## ProfileCriteria

**Description**: User preferences used for scoring (single-user).

**Fields**:
- id (uuid or int)
- preferredTechnologies (json array, default empty)
- seniority (string, required; allowed: junior, mid, senior, lead)
- locations (json array, default empty)
- remoteOnly (boolean, default false)
- createdAt (datetime)
- updatedAt (datetime)

**Validation Rules**:
- seniority must be one of the allowed values.
- preferredTechnologies entries must be canonical names.

## ScrapingRun

**Description**: Audit trail for each scraping execution.

**Fields**:
- id (uuid or int)
- sources (json array)
- queries (json array)
- totalListings (int, default 0)
- uniqueListings (int, default 0)
- newJobs (int, default 0)
- duplicates (int, default 0)
- status (enum: RUNNING, COMPLETED, FAILED)
- startedAt (datetime)
- completedAt (datetime, nullable)

**Validation Rules**:
- sources and queries arrays must not be empty for RUNNING state.

## State Transitions

**Job.status**:
- FOUND -> CONTACTED -> INTERVIEWING -> ACCEPTED
- FOUND -> CONTACTED -> REJECTED
- INTERVIEWING -> REJECTED
- Any state -> REJECTED (explicit override)

**ScrapingRun.status**:
- RUNNING -> COMPLETED
- RUNNING -> FAILED
