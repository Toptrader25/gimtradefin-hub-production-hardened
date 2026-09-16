<?php

namespace App\Console\Commands;

use App\Services\LeadHunterBridgeService;
use Illuminate\Console\Command;

class SyncLeadHunterPublications extends Command
{
    protected $signature = 'leadhunter:sync-published';

    protected $description = 'Syncs Lead Hunter publication_decisions marked "publishable" into the public-facing Opportunity/Company tables (as status=verified, not published — see LeadHunterBridgeService).';

    public function handle(LeadHunterBridgeService $bridge): int
    {
        $result = $bridge->syncPublishedDecisions();

        $this->info("Synced: {$result['synced']}");

        if (! empty($result['errors'])) {
            $this->error(count($result['errors']) . ' error(s):');
            foreach ($result['errors'] as $error) {
                $this->line("  - decision {$error['decision_id']}: {$error['message']}");
            }
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
