# GiMtradefin Lead Hunter — Consolidated Stage 1–10 Deployment Readiness

This bundle is the consolidated Lead Hunter module covering Stages 1–10 plus the multilingual and semantic layers.

## Important

This is **not a fresh standalone Laravel framework installation**. It is a production-oriented module intended to be merged into the Laravel backend your developer deploys on the Lead Hunter VPS.

Do not deploy the individual stage ZIPs. Use this consolidated package as the master source.

## Pre-deployment requirements

- Existing Laravel application (PHP 8.2+ recommended)
- PostgreSQL 14+ (or the version selected by the backend team)
- Composer
- `guzzlehttp/guzzle` 7.9+
- `symfony/dom-crawler` 7+
- `symfony/css-selector` 7+
- Laravel Sanctum configured for internal API authentication
- Queue worker infrastructure for scans/enrichment at production scale
- HTTPS/TLS
- Separate database credentials and secrets from source control

## Required production controls

1. Keep all internal Lead Hunter routes behind `auth:sanctum` and `throttle:api`.
2. Keep source connectors disabled until the source's permitted API/feed/licence/automation basis is confirmed.
3. Never expose raw source payloads through a public endpoint.
4. Never publish a lead without Stage 8 verification/publication decision.
5. Store API keys only in environment/secret storage.
6. Run queue workers under a process supervisor.
7. Back up PostgreSQL and raw evidence storage.
8. Configure log rotation and monitoring.
9. Configure CORS only for the WordPress frontend/API origins that are actually required.
10. Use least-privilege database and VPS accounts.

## Migration order

The twelve migrations are intentionally timestamped 000001 through 000012 and should be run in order with:

```bash
php artisan migrate --force
```

## Initial smoke test

```bash
php artisan route:list
php artisan migrate:status
php artisan leadhunter:data-quality --scope=all
php artisan leadhunter:learning-governance
```

Do not enable real source scanning until authentication, queueing, backups and source permissions are confirmed.

## First production source

Enable exactly one approved source first. Confirm:

- fetch succeeds;
- payload is stored;
- evidence manifest is created;
- candidate is created or deduplicated;
- entity resolution runs;
- trust/risk assessment runs;
- intent assessment runs;
- verification case can be created;
- publication remains blocked until human approval.

Only then expand the source registry.
