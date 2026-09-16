# Stage 7 — AI Opportunity & Intelligent Matching Engine

## Purpose
Turn the evidence-backed commercial profiles produced by Stages 1–6 into explainable opportunity recommendations. The engine is deliberately hybrid: deterministic compatibility rules provide the baseline, while later AI/ML models can rerank candidates once reviewed outcomes exist.

## Core principle
AI must recommend, not invent. Every match retains its underlying entity profiles, intent, evidence, gaps and risks. No match is automatically published or contacted.

## Matching dimensions
- Product compatibility
- Industry compatibility
- Geographic/market fit
- Capability fit
- Commercial terms
- Commercial intent
- Profile confidence
- Evidence/source coverage

Investment matching uses different weights because product compatibility is less important than industry, market, intent and profile quality.

## Explainability
Each match stores:
- score
- confidence
- recommendation tier
- dimension scores
- positive evidence
- gaps
- risk flags
- human-readable explanation

This follows trustworthy-AI principles emphasizing validity, transparency, explainability, monitoring and defined human oversight. NIST's AI RMF specifically recommends documenting human roles, oversight, third-party data risks and explainability/interpretability. 

## Recommendation tiers
- strong_match: high compatibility and confidence
- review: potentially useful but requires human review
- weak: retained for analysis but not promoted

## Important safeguards
1. A high match score is not proof of a legitimate business relationship.
2. Trust/risk remains separate from compatibility.
3. Intent remains separate from company credibility.
4. Independent evidence matters more than duplicated source content.
5. AI/ML should not be trained as a production decision-maker until human feedback and outcome data are available.
6. Human reviewers can accept, reject, or qualify matches; feedback is stored for later calibration.

## API
- `GET /api/v1/matching/entity/{entityId}?limit=25`
- `GET /api/v1/matching/{matchId}/explain`
- `POST /api/v1/matching/{matchId}/feedback`

## CLI
`php artisan leadhunter:match {entity_id} --limit=25`

## Next evolution
After real reviewed matches accumulate, introduce model-assisted reranking, learning-to-rank evaluation, multilingual semantic similarity, product taxonomy embeddings, counterparty graph signals and outcome-calibrated probability of successful engagement. Do not replace the evidence/explanation layer with a black-box score.
