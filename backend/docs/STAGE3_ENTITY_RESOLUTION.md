# Stage 3 — Entity Resolution / Golden Entity Graph

Stage 3 is the identity layer of GiMtradefin Lead Hunter. It solves the problem that the same business can appear on many sources under different spellings, legal suffixes, languages, domains, phones, addresses and registration identifiers.

## The key distinction

A **candidate** is a source observation. An **entity** is GiMtradefin's canonical representation of the underlying business/person. We never overwrite the observation to make it look clean; the original observation remains available for evidence and audit.

## Resolution pipeline

```text
Source candidate
  ↓
Immutable-ish observation
  ↓
Normalization
 ├─ business-name normalization
 ├─ legal-suffix normalization
 ├─ domain normalization
 ├─ phone normalization
 ├─ registration/tax/LEI normalization
 ├─ address normalization
 └─ multilingual transliteration
  ↓
Deterministic identifier lookup
  ↓
Candidate generation
  ↓
Explainable multi-signal scoring
  ↓
Identity decision
 ├─ auto_link (only with hard identity evidence)
 ├─ review
 └─ reject
  ↓
Canonical entity graph
```

## Identity evidence hierarchy

### Tier 1 — strongest

- Company registration number
- Tax/VAT identifier
- LEI
- Other jurisdiction-specific official identifier

### Tier 2 — strong

- Corporate domain + strong name similarity
- Exact verified phone + very strong name similarity

### Tier 3 — supporting

- Name similarity
- Transliteration similarity
- Country
- Address
- Email domain
- Source repetition

**No name-only match can auto-link.** This prevents common names such as “Global Trading”, “ABC International” or their translations from collapsing unrelated companies into one entity.

## Multilingual identity

The normalizer keeps the original name and can create a transliterated representation using ICU's `Transliterator` when PHP's intl extension is available. This is important for Chinese, Vietnamese, Arabic, Cyrillic and other non-Latin sources.

The transliterated value is a matching aid only; it does not replace the original legal/company name.

## Scoring

Current explainable features:

- normalized name similarity
- transliterated name similarity
- exact domain
- exact registration number
- exact tax ID
- exact phone
- exact email domain
- country
- address similarity

Weights are intentionally conservative. Hard identifiers can raise the score, but a fuzzy name alone cannot cross the automatic-link threshold.

### Decision policy

- **≥ 0.94 + hard identity evidence:** `auto_link`
- **0.60–0.9399:** `review`
- **< 0.60:** no existing-entity link; a new entity may be created

The thresholds are configuration candidates, not permanent truths. After Stage 4 and human-review data exist, they should be calibrated using false-positive/false-negative measurements.

## Entity graph

`entities` — canonical organization/person/unknown.

`entity_aliases` — all meaningful observed names.

`entity_identifiers` — normalized identity keys and their verification status.

`entity_observations` — source-specific observations at a point in time.

`entity_match_candidates` — candidate matches, scores, features and reasons.

`entity_links` — source candidate → canonical entity relationships.

`entity_merge_events` — future-safe audit history for explicit merges/unmerges.

## Critical production rules

1. Never delete source observations after resolution.
2. Never let an AI model be the sole identity resolver.
3. Never expose resolution confidence as business verification.
4. Never auto-merge on company name alone.
5. Treat free email providers (Gmail, Outlook, Yahoo, etc.) as weak identity evidence; do not let them identify an organization.
6. Treat shared corporate domains carefully; a parent group can own several entities.
7. Store why a match happened, not only the score.
8. Every automatic link must be reversible.
9. Manual merges must require a reason and preserve a before/after snapshot.
10. Entity identity and commercial credibility remain separate concepts.

## What makes this commercially valuable

Once the same entity is resolved across sources, GiMtradefin can answer questions that a simple B2B directory cannot:

- Has this buyer appeared elsewhere?
- What products does the buyer repeatedly seek?
- Which countries does it source from?
- Which suppliers has it interacted with?
- Is its activity increasing or declining?
- Is the same opportunity being reposted?
- Does the company look like a real operating business across independent sources?

Those capabilities become inputs to Stage 4 (duplicate/fraud/trust) and Stage 5 (commercial intent).
