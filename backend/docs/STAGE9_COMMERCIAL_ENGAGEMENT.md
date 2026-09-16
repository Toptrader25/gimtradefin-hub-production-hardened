# Stage 9 — Commercial Engagement & Opportunity Management Engine

Stage 9 converts an approved intelligence opportunity into a controlled commercial workflow.

## Core flow
Approved opportunity → verified contact → evidence-backed outreach draft → human/provider send → response → qualification → introduction → meeting → RFQ → quote → negotiation → won/lost/nurture.

## Safety and integrity principles
- AI may draft and classify; it must not silently send external commercial communications.
- Contact verification is separate from entity verification.
- Original evidence remains the source of truth.
- Outreach should use only supported facts; no invented company claims, prices, volumes or relationships.
- Every external communication and material pipeline decision is auditable.
- Outcomes feed later scoring/model calibration but are not treated as proof of causation.

## Pipeline stages
approved, contacted, engaged, qualified, introduced, meeting, rfq, quoted, negotiation, won, lost, nurture.

## Channels
Email, WhatsApp, LinkedIn, phone, SMS and other controlled channels. Provider integrations are deliberately separated from the core engagement database.

## Next-best-action
The service generates deterministic recommendations based on stage, contact verification, open tasks and recorded outcomes. A later AI layer can rank these actions using real outcome data.

## Installation
1. Apply the new migration with `php artisan migrate`.
2. Register `routes/engagement.php` in the application's API route bootstrap.
3. Register `App\\Services\\CommercialEngagementService` through Laravel container discovery or standard autoloading.
4. Connect an approved messaging provider only after consent, privacy, provider policy and anti-spam controls are established.
5. Run the Stage 9 unit tests.
