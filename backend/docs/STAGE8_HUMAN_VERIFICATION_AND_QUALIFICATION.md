# Stage 8 — Human Verification & Commercial Qualification

## Objective
Turn an AI-discovered opportunity into a controlled, auditable commercial decision. AI recommends; a defined human workflow decides whether the opportunity can be published, conditionally published, held, or rejected.

## Workflow
1. **Queue** — case created with priority and SLA.
2. **Identity checks** — confirm company existence, legal-name consistency, domain, and identifier evidence.
3. **Evidence checks** — confirm primary source, provenance integrity, independent corroboration and material consistency.
4. **Commercial checks** — confirm current intent, specificity, role and contact relevance.
5. **Risk checks** — resolve critical risk flags, complete appropriate sanctions screening, and complete privacy review.
6. **Qualification** — record structured answers and scores; do not infer facts that are not evidenced.
7. **Decision** — approve, approve with conditions, request more evidence, hold, or reject.
8. **Publication gate** — approval is blocked until critical checks and evidence requirements are satisfied.
9. **Audit** — every check, override, decision and reason is recorded.

## Verification states
- `pending`
- `verified`
- `failed`
- `not_applicable`
- `needs_review`

## Decision states
- `approve`
- `approve_with_conditions`
- `request_more_evidence`
- `hold`
- `reject`

## Core rule
A high AI match score is **not** permission to publish. Publication requires sufficient evidence and human review. This follows a human-AI configuration in which responsibilities are explicit and AI output remains reviewable and auditable. NIST emphasizes defining human roles and responsibilities, monitoring performance, and using human intervention where automated systems cannot reliably detect or correct errors.

## Recommended reviewer questions
### Identity
- Does the entity appear to be a real operating business?
- Do the name, domain, location and identifiers align?
- Is the person/contact role relevant to the commercial activity?

### Opportunity
- Is there evidence the requirement/offer is current?
- Is the product/service specific enough to act on?
- Is quantity, timing, market or commercial terms sufficiently clear?

### Evidence
- Is the original source retained?
- Is provenance intact?
- Is there independent corroboration?
- Are contradictions resolved or disclosed?

### Risk
- Are there unresolved critical risk flags?
- Has the appropriate screening been completed?
- Does the record expose unnecessary personal information?

### Qualification
- What exactly is being bought/sold/financed/partnered?
- Who is the counterparty?
- What is the commercial timing?
- What evidence supports the opportunity?
- What is still unknown?

## Important boundary
The system must never state that a company is fraudulent merely because a risk rule fired. Use neutral labels such as `risk_signal`, `requires_review`, or `evidence_gap` and retain the underlying evidence.
