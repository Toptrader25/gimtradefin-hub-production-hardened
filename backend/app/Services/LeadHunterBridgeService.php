<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Evidence;
use App\Models\Opportunity;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;

/**
 * Connects the Lead Hunter package (source_candidates → entities →
 * verification_cases → publication_decisions) to the public-facing
 * Opportunity/Company tables that the Next.js frontend actually reads.
 *
 * Without this service, Lead Hunter's output — however sophisticated —
 * never appears anywhere a user can see it. This was the real gap
 * identified when the package was inspected: it has its own complete
 * data model and never writes to `opportunities` at all.
 *
 * DELIBERATE SAFETY DECISION: bridged opportunities land as status
 * "verified", never "published", even though Lead Hunter's own
 * publication_decision already represents a human reviewer's approval.
 * That reviewer isn't authenticated against this app's User/role system
 * — trusting it to control public visibility directly would collapse
 * the two-gate review model used everywhere else in this codebase.
 * A GiMtradefin admin still has to click "Publish" in the dashboard.
 */
class LeadHunterBridgeService
{
    /**
     * Maps a Lead Hunter signal_type to this app's category enum.
     * Lead Hunter doesn't have a buying/selling/partnership concept of
     * its own, so this is a best-effort heuristic, not a guarantee —
     * every sync records the original signal_type in the audit log so
     * a reviewer can correct a wrong guess.
     */
    private function mapCategory(?string $signalType, ?string $sourceRole): string
    {
        $haystack = strtolower(($signalType ?? '') . ' ' . ($sourceRole ?? ''));

        if (str_contains($haystack, 'sell') || str_contains($haystack, 'supplier')) {
            return 'selling';
        }
        if (str_contains($haystack, 'partner') || str_contains($haystack, 'distributor') || str_contains($haystack, 'jv')) {
            return 'partnership';
        }
        // Default: 'buyer_request' and generic 'commercial_signal' both
        // land here. Genuinely ambiguous — flagged via the audit log.
        return 'buying';
    }

    /**
     * Finds every publication_decision marked "publishable" that hasn't
     * been synced yet, and creates a corresponding verified (not
     * published) Opportunity + Company for each. Idempotent — safe to
     * run repeatedly, e.g. on a schedule.
     *
     * @return array{synced: int, skipped_already_synced: int, errors: array}
     */
    public function syncPublishedDecisions(): array
    {
        $decisions = DB::table('publication_decisions')
            ->where('visibility', 'publishable')
            ->whereNotIn('id', function ($q) {
                $q->select('publication_decision_id')->from('lead_hunter_bridge_syncs');
            })
            ->get();

        $synced = 0;
        $errors = [];

        foreach ($decisions as $decision) {
            try {
                $this->syncOne($decision);
                $synced++;
            } catch (\Throwable $e) {
                $errors[] = ['decision_id' => $decision->id, 'message' => $e->getMessage()];
            }
        }

        return [
            'synced' => $synced,
            'errors' => $errors,
        ];
    }

    private function syncOne(object $decision): void
    {
        $case = DB::table('verification_cases')->where('id', $decision->case_id)->first();
        if (! $case) {
            throw new \RuntimeException("Verification case {$decision->case_id} not found for decision {$decision->id}");
        }

        $candidate = $case->candidate_id
            ? DB::table('source_candidates')->where('id', $case->candidate_id)->first()
            : null;

        $entity = $case->entity_id
            ? DB::table('entities')->where('id', $case->entity_id)->first()
            : null;

        if (! $candidate && ! $entity) {
            throw new \RuntimeException("Case {$case->id} has neither a candidate nor an entity — nothing to bridge");
        }

        // Pull the source's configured role/signal, if we can find it,
        // for a slightly better category guess than signal_type alone.
        $sourceRole = null;
        if ($candidate && $candidate->source_slug) {
            $sourceConfig = config("sources.{$candidate->source_slug}");
            $sourceRole = $sourceConfig['role'] ?? null;
        }

        $category = $this->mapCategory($candidate->signal_type ?? null, $sourceRole);

        // Find-or-create the Company from the resolved entity. Lead
        // Hunter's own entity resolution (Stage 3) already did the hard
        // work of deduplication — this just mirrors its output into our
        // Company table, matched by name+country same as the manual
        // submission path.
        $company = null;
        if ($entity) {
            $company = Company::firstOrCreate(
                ['name' => $entity->canonical_name, 'country' => $entity->country_name ?? 'Unknown'],
                ['role' => 'Discovered via Lead Hunter', 'verification_status' => 'pending']
            );
        }

        $title = $candidate->title ?? ($entity->canonical_name ?? 'Untitled opportunity');
        $description = $candidate->description ?? 'No description captured by the source connector.';
        $country = $candidate->country ?? $entity->country_name ?? 'Unknown';

        // Pull the latest risk/intent scores if Lead Hunter's scoring
        // pipeline has run for this subject — best-effort, left null
        // rather than guessed if unavailable.
        $riskAssessment = $candidate
            ? DB::table('risk_assessments')->where('subject_type', 'candidate')->where('subject_id', $candidate->id)->orderByDesc('calculated_at')->first()
            : null;
        $intentAssessment = $candidate
            ? DB::table('intent_assessments')->where('subject_type', 'candidate')->where('subject_id', $candidate->id)->orderByDesc('calculated_at')->first()
            : null;

        $opportunity = Opportunity::create([
            'title'                     => $title,
            'category'                  => $category,
            'description'               => $description,
            'country'                   => $country,
            'status'                    => 'verified', // see class docblock — deliberately not 'published'
            'company_id'                => $company?->id,
            'commercial_intent_score'   => $intentAssessment->intent_score ?? null,
            'source_reliability_score'  => $riskAssessment ? max(0, 100 - $riskAssessment->risk_score) : null,
            'verified_at'               => now(),
        ]);

        // Preserve the evidence trail in OUR system too, not just
        // Lead Hunter's — same "where did this come from" principle
        // applied consistently everywhere else in this app.
        Evidence::create([
            'opportunity_id'    => $opportunity->id,
            'discovered_at'     => $candidate->first_seen_at ?? now(),
            'evidence_type'     => 'lead_hunter_pipeline',
            'original_url'      => $candidate->url ?? null,
            'excerpt'           => sprintf(
                'Discovered and verified via the Lead Hunter pipeline. Verification case %s, decision reason: %s.',
                $case->id,
                $decision->reason_code ?? 'unspecified'
            ),
            'source_confidence' => $riskAssessment ? max(0, 100 - $riskAssessment->risk_score) : null,
        ]);

        DB::table('lead_hunter_bridge_syncs')->insert([
            'publication_decision_id'   => $decision->id,
            'verification_case_id'      => $case->id,
            'opportunity_id'            => $opportunity->id,
            'mapped_category'           => $category,
            'mapped_from_signal_type'   => $candidate->signal_type ?? null,
            'created_at'                => now(),
            'updated_at'                => now(),
        ]);

        AuditLog::create([
            'user_id'        => null, // system-initiated sync, not a person's action
            'auditable_type' => Opportunity::class,
            'auditable_id'   => $opportunity->id,
            'action'         => 'lead_hunter_bridge_synced',
            'changes'        => [
                'verification_case_id'   => $case->id,
                'mapped_category'        => $category,
                'mapped_from_signal_type'=> $candidate->signal_type ?? null,
                'note'                   => 'Category is a best-effort heuristic guess — verify before publishing.',
            ],
        ]);
    }
}
