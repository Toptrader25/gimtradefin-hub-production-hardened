# Stage 4 — Trust, Risk, Duplicate & Abuse Intelligence

## Objective

Stage 4 does **not** declare a company fraudulent. It produces explainable risk signals, duplicate/near-duplicate relationships, and a review decision. Evidence remains the source of truth.

## Risk model

The engine combines:
- identity completeness
- verified identifiers
- cross-source presence
- freshness/activity
- cross-source contradictions
- content risk language
- credential/payment red flags
- source velocity anomalies
- exact and near-duplicate opportunity signals

Risk is 0–100 and is mapped to:
- 0–24 low
- 25–49 guarded
- 50–74 high
- 75–100 critical

Decisions:
- allow
- review
- restrict
- suppress

These are **workflow decisions**, not allegations of fraud.

## Duplicate intelligence

Each candidate receives deterministic fingerprints and component fingerprints for:
- company/entity
- product
- country
- lead type
- title
- normalized content

The system detects exact and near duplicates and stores cluster membership rather than deleting source records. This preserves provenance and allows recurring opportunities to be distinguished from copied listings.

## Risk signals

Initial production rules include:
- exact duplicate
- near duplicate
- missing source URL
- no verified identifier
- no business domain
- stale entity activity
- cross-source presence (positive signal, not a fraud signal)
- country contradiction
- domain variation
- source velocity spike
- credential/OTP/password requests
- suspicious payment language
- guaranteed profit / risk-free language
- urgency without sufficient detail

Free email domains are **never** treated as fraud on their own.

## Human review

High-risk candidates/entities create a review case. Reviewers should be able to:
- confirm risk
- dismiss a signal
- request verification
- restrict an entity/opportunity
- restore a previously restricted record
- leave an auditable reason

No automatic accusation, permanent ban, or public fraud label is produced by this engine.

## Security baseline

Use OWASP ASVS as the application-security baseline and apply adaptive/risk-based controls to sensitive operations. OWASP recommends risk-based fraud controls, rate limiting, behavioral signals and review queues. See the implementation references in the project README.

## Production limitations

This stage intentionally does not use commercial fraud databases, sanctions/watchlists, device fingerprinting, IP reputation vendors, or external identity/KYC services. Those should be added only through lawful/licensed providers and a documented data-protection basis.

## Next stage

Stage 5 should consume Stage 4 outputs to calculate **commercial intent**, explicitly separating:
- trust/risk
- evidence confidence
- commercial intent

A low-risk company is not automatically a good commercial opportunity, and a high-intent opportunity is not automatically trustworthy.
