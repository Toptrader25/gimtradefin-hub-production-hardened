# Stage 1 Source Policy

## Active means two things

A source is scanned only when BOTH are true:

1. `enabled=true`
2. `permission_confirmed=true`

This prevents a developer from accidentally turning on a source merely because its URL is known.

## Source classes

**A — Approved live feed/API:** preferred. Use the provider's API, RSS/Atom, webhook, export, or licensed dataset.

**B — Approved public page:** permitted public HTML page with clear access rights/terms and conservative rate limiting.

**C — Authentication/licensed:** requires credentials, subscription, partner access or licensed dataset. Integrate through the approved API/export; do not automate login or bypass controls.

**D — Discovery only:** known real source, not yet authorized for automated collection.

## Evidence rule

Stage 1 stores the source payload and candidate extraction. It does not claim the candidate is genuine. Later stages must verify identity, commercial intent and authenticity.

## Commercial-first source weighting

Default source priority:

1. Explicit buyer requests / RFQs / sourcing requests
2. Distributor / partnership requests
3. Supplier/exporter offers
4. Company expansion / sourcing signals
5. Trade intelligence
6. Government procurement / aggregate public data

Government sources should support intelligence rather than dominate the opportunity database.
