# Stage 7 — Semantic Opportunity Layer

This layer strengthens matching without inventing equivalence. It normalizes products, industries, capabilities, markets, HS-code prefixes, quantities, prices, currencies and trade terms before scoring.

## Principles

1. Preserve source wording and evidence; normalization is a derived representation.
2. Use controlled concepts before fuzzy similarity.
3. HS-code matches are strong evidence only when codes are actually present.
4. Quantities are normalized by unit; no currency conversion is performed unless an approved FX provider is later configured.
5. Route feasibility is a conservative regional signal, not a freight quote or legal determination.
6. Embeddings/LLMs are optional and must be evaluated against reviewed matches before affecting production scores.
7. Every semantic score must be explainable and traceable to normalized facts.

## Production path

Source evidence → multilingual normalization → canonical commercial concepts → entity profile → semantic compatibility → match score → explanation → human review → outcome feedback.

## Later upgrades

- Licensed HS taxonomy and versioning
- UN/ISO country and currency codes
- Product ontology with multilingual labels
- Embedding provider behind a feature flag
- Cross-encoder/reranker for top-N candidates
- Incoterm/payment-term compatibility matrix
- FX normalization using dated rates
- Logistics lane/serviceability data
- Learning-to-rank from human and deal outcomes
- Evaluation sets and precision/recall monitoring
