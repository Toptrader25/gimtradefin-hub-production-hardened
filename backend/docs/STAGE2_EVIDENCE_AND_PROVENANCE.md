# Lead Hunter Stage 2 — Evidence & Provenance

## Objective

Stage 2 makes every discovered commercial candidate traceable to the exact source payload from which GiMtradefin obtained it.

The rule is simple:

> **No evidence, no trust. No traceability, no publication.**

Stage 2 does not create leads. It records and verifies evidence for leads discovered by Stage 1.

## Evidence chain

```text
Permitted source
    ↓
Ingestion run
    ↓
Raw source payload
    ↓
SHA-256 fingerprint
    ↓
Evidence manifest
    ↓
Candidate field
    ↓
Exact/normalized match in captured payload
    ↓
Evidence excerpt + locator + excerpt hash
    ↓
Integrity verification
    ↓
Evidence quality score
```

## What is stored

### evidence_manifests
A signed-style provenance record for each captured payload:

- source
- canonical URL
- HTTP status
- content type
- payload SHA-256
- byte count
- capture timestamp
- retrieval method
- permission status

### candidate_evidence
Evidence attached to individual candidate fields such as title and description:

- original field value
- locator
- source excerpt
- excerpt SHA-256
- verification status
- verification method
- confidence
- capture timestamp

### evidence_integrity_checks
Independent checks that the current stored payload still produces the original SHA-256 hash.

## Evidence quality

Candidates receive an evidence score from 0–100.

The current weighting is deliberately conservative:

- 45% verified field coverage
- 30% payload integrity
- 15% permission confirmation
- 10% freshness

A candidate is marked `publishable` only when:

- score >= 75
- at least 50% of captured evidence fields are verified
- payload integrity passes
- source permission is confirmed

This is **not** the final commercial score. That belongs to later stages.

## Important limitation

The Stage 2 HTML matcher is intentionally conservative. It verifies that extracted title/description text can be located in the captured payload. It does not claim that the source itself is truthful, that the company exists, or that the person is authorized to sell/buy. Those are separate stages.

## API endpoints

Authenticated endpoints:

- `GET /api/v1/candidates/{id}/evidence`
- `GET /api/v1/candidates/{id}/evidence-summary`
- `GET /api/v1/evidence/{manifestId}`
- `POST /api/v1/evidence/{manifestId}/verify`

## CLI

Verify all evidence manifests:

```bash
php artisan leadhunter:evidence-verify
```

Verify one manifest:

```bash
php artisan leadhunter:evidence-verify <manifest-uuid>
```

## Production principle

Do not delete the original payload merely because a parser has normalized the record. The normalized candidate is the product layer; the raw payload is the audit layer.
