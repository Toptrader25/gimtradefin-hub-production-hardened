<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
| REQUIRES a real cron entry on the server — this file alone does
| nothing. Add to crontab:
|   * * * * * cd /path-to-app && php artisan schedule:run >> /dev/null 2>&1
|
| Pipeline order matters: each stage depends on the previous one's
| output. source:scan is safe to run even with every source disabled —
| the connector itself refuses to run for a disabled/unconfirmed source
| (see SourceScanner), so this schedule is inert until a source is
| deliberately enabled with confirmed permission.
*/

Schedule::command('source:scan --enabled')->hourly()
    ->description('Lead Hunter: scan all enabled sources for new candidates');

Schedule::command('leadhunter:resolve-entities --limit=200')->hourlyAt(15)
    ->description('Lead Hunter: resolve newly scanned candidates into entities');

Schedule::command('leadhunter:data-quality')->dailyAt('02:00')
    ->description('Lead Hunter: Stage 10 data quality checks');

Schedule::command('leadhunter:source-scorecards')->dailyAt('02:15')
    ->description('Lead Hunter: build source performance scorecards');

// leadhunter:assess-risk, leadhunter:intent, and leadhunter:evidence-verify
// take a specific candidate/entity ID and aren't safe to blanket-schedule
// without a "process everything new" wrapper — left as manual/API-driven
// for now rather than guessing at a batch design that hasn't been built.

// leadhunter:verification-case requires a --reviewer, i.e. a human
// decision made off-band — deliberately never scheduled automatically.
// See EnsureUserIsReviewer and the two-gate publish model this whole
// app is built around: a cron job doesn't get to be "the reviewer".

Schedule::command('leadhunter:sync-published')->everyFifteenMinutes()
    ->description('Bridge: sync Lead Hunter publication_decisions into the public Opportunity/Company tables (as verified, not published)');
