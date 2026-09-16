# Stage 5 — Commercial Intent Engine

## Purpose

Determine whether an evidence-backed record indicates a real commercial intention to **buy, sell, partner or seek investment**, and how current/strong that intention is.

The engine does **not** infer an opportunity from a generic company profile. A company being a manufacturer, exporter or importer is not by itself a current lead.

## Intent hierarchy

1. **Declared** — explicit current commercial language: RFQ, buyer request, supplier search, distributor wanted, partner sought, etc.
2. **Implied** — structured commercial details such as quantity, deadline, target price, Incoterms, MOQ, capacity.
3. **Inferred** — behavioural or contextual signals. These are intentionally not treated as equivalent to declared intent and will be expanded only when the system has validated behavioural sources.

## Core outputs

- `intent_score`: 0–100
- `intent_stage`: `no_signal`, `early_signal`, `research`, `evaluation`, `active_purchase`, `cooling`
- `buying_temperature`: 0–5
- `confidence`: 0–1
- positive/negative signal strength
- independent source count
- dimension scores
- explainable top signals

## Signal decay

The default half-life is 30 days. A recent explicit request therefore carries materially more weight than an identical old request. Stale records create a negative signal rather than being silently treated as current demand.

## Corroboration

Independent sources add a modest capped bonus. Source count cannot manufacture high intent because the engine separately records signal confidence and source provenance.

## Anti-gaming rules

- Duplicate copies of the same source do not count as independent evidence.
- Generic company descriptions do not create buying intent.
- AI-generated text cannot be used as primary intent evidence.
- A high score does not equal verification or legitimacy.
- Intent and trust/risk are separate dimensions.
- All non-first-party signals must retain a source/evidence reference.

## Recommended production calibration

Initial weights are deterministic and explainable. After human verification and downstream outcomes exist, the scoring layer should be calibrated against actual outcomes (contacted, responded, qualified, quote, negotiation, deal). Never replace the explainable event ledger with a black-box score.
