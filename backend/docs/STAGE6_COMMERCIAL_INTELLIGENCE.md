# Stage 6 — Commercial Intelligence Engine

## Purpose
Transform evidence-backed entity observations into structured commercial intelligence. Stage 6 answers: what commercial role does an entity appear to play, what does it buy/sell/offer, which industries/products/markets are associated with it, what trade terms or volumes are evidenced, and whether it appears to be an investor or capital seeker.

## Core roles
- buyer
- seller
- partner
- investor
- capital_seeker
- hybrid entities are represented through multi-role scores; `primary_role` is only the highest current role.

## Investment intelligence
Investment is deliberately split into **investor** and **capital_seeker**. A company looking for funding is not an investor merely because the text contains the word investment. Investment facts include funding amount, investment amount, funding stage and investment purpose when the source supplies them.

## Evidence discipline
Stage 6 does not invent capabilities, products, volumes, counterparties or investment facts. Facts are only created from structured candidate attributes or evidence-backed observations. Source confidence influences the fact confidence. Inferred classifications are stored as scores, not as verified facts.

## Profile components
- roles and role scores
- industries
- products
- markets/countries
- capabilities
- commercial terms
- investment profile
- commercial facts with source/candidate references
- historical profile snapshots
- entity-to-entity relationship table for later relationship intelligence

## Important distinction
A high commercial-intelligence profile does not mean an entity is verified, creditworthy or safe. Stage 4 Trust & Risk remains authoritative for risk. Stage 5 Commercial Intent remains authoritative for current intent. Stage 6 describes the entity's commercial characteristics and role.

## Production hardening before scale
1. Add controlled vocabularies/taxonomies per product and industry.
2. Add multilingual NER/entity extraction for Chinese, Vietnamese, Arabic, Japanese, Korean and European languages.
3. Link every fact to a specific evidence record wherever possible.
4. Add time validity and supersession rules for changing facts.
5. Add relationship verification and parent/subsidiary resolution.
6. Calibrate role scores against human-reviewed outcomes.
7. Add licensed corporate/trade intelligence providers where permitted.
8. Never infer financial capacity or beneficial ownership without authoritative evidence.
