# Research: Multi-Source Job Aggregation & Intelligence

## Pagination Strategy

- **Decision**: Paginate per source/query until the source returns an empty page
  or fewer results than page size, with a configurable max pages limit.
- **Rationale**: Stops deterministically while allowing full coverage of
  available results.
- **Alternatives considered**: Hard page cap only; time-based stop criteria.

## Deduplication Strategy

- **Decision**: Deduplicate within a run using externalId + source key when
  available; otherwise fall back to normalized title + company + location + date.
- **Rationale**: External identifiers are the most reliable; fallback reduces
  duplicates when sources lack stable IDs.
- **Alternatives considered**: Title-only dedupe (too lossy), URL-only dedupe
  (inconsistent across sources).

## Scoring Inputs and Scale

- **Decision**: Score based on seniority match, technology overlap, and location
  preference (including remote-only), using a 0-100 scale.
- **Rationale**: Aligns with the prioritized criteria and provides a clear
  ranking for CLI output.
- **Alternatives considered**: 0.0-1.0 float scale; tiered labels only.

## Technology Extraction

- **Decision**: Use a curated internal dictionary of known technologies to
  extract stack mentions, normalized to canonical names.
- **Rationale**: Deterministic, auditable, and easy to extend without ML.
- **Alternatives considered**: ML-based NER; regex-only with no normalization.

## Publish Date Parsing

- **Decision**: Parse published dates from source metadata where available; if
  only relative dates are provided, convert to absolute timestamps. Default
  recency window is 30 days.
- **Rationale**: Enables consistent recency filtering across sources.
- **Alternatives considered**: Ignore missing dates; keep as relative strings.

## Rate Limiting and Backoff

- **Decision**: Implement per-source throttling with exponential backoff on HTTP
  429/5xx, and respect `--limit` as a per-combination cap.
- **Rationale**: Protects against blocks while keeping predictable throughput.
- **Alternatives considered**: Global rate limiting only; fixed sleep delays.

## Status Update Precedence

- **Decision**: Manual status changes override any automated status updates.
- **Rationale**: Preserves user control over the pipeline state.
- **Alternatives considered**: System status overrides manual edits.
