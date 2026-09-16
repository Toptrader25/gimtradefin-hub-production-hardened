# Stage 10 — Learning, Governance & Scale Engine

Stage 10 turns Lead Hunter into an auditable, continuously improving commercial intelligence platform. It is deliberately outcome-driven rather than pretending that a model is accurate because it produces a score.

## Core architecture

Evidence → Entity → Trust → Intent → Commercial Intelligence → Matching → Human Verification → Engagement → **Outcome** → Learning → Calibration → Governance → Controlled deployment.

## Major capabilities

### 1. Outcome learning ledger
Every material event can become a learning event: verified, rejected, duplicate, contacted, responded, qualified, RFQ, quote, won, lost, nurture and other reviewed outcomes. Events preserve subject, opportunity, feature snapshot, actor and time.

### 2. Immutable-ish feature snapshots
Features used for decisions are versioned and hashed. This makes later model evaluation reproducible and helps prevent silent feature changes from contaminating historical analysis.

### 3. Model registry and promotion controls
Models/rules have explicit versions and lifecycle states: candidate → production → retired. Production promotion records the actor and approval metadata. Never deploy a new model directly from training code.

### 4. Prediction registry
Every prediction stores model version, probability/score, explanation, feature payload and later outcome. This is the foundation for calibration and learning-to-rank.

### 5. Calibration and evaluation
The schema supports Brier score, log loss, ROC-AUC, PR-AUC, confusion matrices and calibration bins. For commercial ranking, evaluate precision@K, recall@K, NDCG@K, qualified-rate@K and won-rate@K as well.

### 6. Drift monitoring
Monitor feature distributions, source mix, score distributions, conversion rates, entity-resolution rates and verification outcomes. Drift is an alert, not an automatic accusation or automatic rollback.

### 7. Source scorecards
Measure each source on evidence quality, duplicates, risk flags, verification, qualification and downstream wins. A source producing 10,000 weak records should not outrank a source producing 100 high-quality opportunities.

### 8. Reviewer quality
Track agreement, overturns, review time and quality. Do not use reviewer metrics as a punitive ranking without governance; use them to identify training needs and inconsistent policy application.

### 9. Governance
Policies can define deny/review/allow rules. Governance events are auditable with correlation IDs and hashed IP metadata. High-risk publication, model promotion and sensitive data access should require explicit authorization.

### 10. Experiments and feature flags
Controlled experiments permit new scoring/matching logic to be evaluated on a bounded population. Deterministic hashing provides stable assignments. Guardrails should be defined before rollout.

### 11. Data quality
Scheduled quality checks identify broken foreign references, missing timestamps, incomplete feature hashes and other integrity problems. Extend this with freshness, uniqueness, validity, consistency, completeness and source-level reconciliation checks.

### 12. Scale principles
Use queues for source ingestion, enrichment, translation and model scoring; idempotency keys for every external operation; backoff/retry with dead-letter queues; partition high-volume event tables by time where supported; indexes on subject/opportunity/time; object storage for raw evidence; PostgreSQL for transactional intelligence; Redis for short-lived cache/locks; and an analytics warehouse later when event volume justifies it.

## Production model lifecycle

1. Define the decision and target outcome.
2. Freeze a feature schema.
3. Build a time-based training/evaluation split.
4. Compare against the current production baseline.
5. Evaluate calibration and ranking metrics.
6. Run fairness/safety/privacy checks appropriate to the use case.
7. Shadow-test the candidate model.
8. Obtain explicit approval.
9. Canary release to a small percentage.
10. Monitor guardrails and business outcomes.
11. Promote or rollback.
12. Retain model, feature and evaluation artifacts.

## Important safeguards

- No model should invent source evidence.
- No model score should itself establish that a company is fraudulent.
- No public verification claim should be made without the Stage 8 verification workflow.
- No automatic external outreach should occur without an approved communication policy.
- Never train on personal data or sensitive information unless there is a documented lawful basis, appropriate minimization and governance.
- Keep original source evidence separate from translated/derived interpretations.
- Keep model predictions separate from observed outcomes.

## Stage 10 success metric

The ultimate objective is not "AI accuracy" in isolation. It is **verified commercial value per unit of review and acquisition cost**, while maintaining low false-positive risk, high evidence quality and a defensible audit trail.
