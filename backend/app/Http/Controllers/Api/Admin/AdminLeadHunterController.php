<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\LeadHunterBridgeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminLeadHunterController extends Controller
{
    /**
     * POST /api/v1/admin/lead-hunter/sync (auth:sanctum + reviewer)
     * Manually triggers the bridge sync. Until a real task scheduler is
     * configured on the server, this button is how new Lead Hunter
     * publications actually become visible in the review queue.
     */
    public function sync(LeadHunterBridgeService $bridge)
    {
        $result = $bridge->syncPublishedDecisions();

        return response()->json($result);
    }

    /**
     * GET /api/v1/admin/lead-hunter/candidates
     * The package itself has no list endpoint for source_candidates —
     * only individual lookups via other services. This is what makes
     * discovered candidates actually browsable, rather than only
     * visible via direct database access.
     */
    public function candidates(Request $request)
    {
        $query = DB::table('source_candidates')->orderByDesc('last_seen_at');

        if ($request->filled('source_slug')) {
            $query->where('source_slug', $request->string('source_slug'));
        }

        $paginated = $query->paginate(25);

        return response()->json([
            'data' => collect($paginated->items())->map(fn ($c) => [
                'id'            => $c->id,
                'title'         => $c->title,
                'source_slug'   => $c->source_slug,
                'country'       => $c->country,
                'signal_type'   => $c->signal_type,
                'url'           => $c->url,
                'first_seen_at' => $c->first_seen_at,
            ]),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/admin/lead-hunter/cases
     * Same gap as candidates() — the package's verification.php only
     * supports looking up ONE case by ID. Without a list view, an
     * operator would have no way to discover which cases exist at all.
     */
    public function verificationCases(Request $request)
    {
        $query = DB::table('verification_cases')->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('decision')) {
            $query->where('decision', $request->string('decision'));
        }

        $paginated = $query->paginate(25);

        return response()->json([
            'data' => collect($paginated->items())->map(fn ($c) => [
                'id'                  => $c->id,
                'entity_id'           => $c->entity_id,
                'candidate_id'        => $c->candidate_id,
                'case_type'           => $c->case_type,
                'status'              => $c->status,
                'priority'            => $c->priority,
                'verification_score'  => $c->verification_score,
                'qualification_score' => $c->qualification_score,
                'decision'            => $c->decision,
                'due_at'              => $c->due_at,
                'created_at'          => $c->created_at,
            ]),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/admin/lead-hunter/engagement-opportunities
     * Same gap pattern as candidates() and verificationCases() — the
     * package only supports POST /engagement/opportunities (create) and
     * GET /engagement/opportunities/{id} (single lookup), with no way
     * to discover which engagement opportunities exist at all.
     */
    public function engagementOpportunities(Request $request)
    {
        $query = DB::table('engagement_opportunities')->orderByDesc('updated_at');

        if ($request->filled('stage')) {
            $query->where('stage', $request->string('stage'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $paginated = $query->paginate(25);

        return response()->json([
            'data' => collect($paginated->items())->map(fn ($o) => [
                'id'            => $o->id,
                'title'         => $o->title,
                'stage'         => $o->stage,
                'status'        => $o->status,
                'owner'         => $o->owner,
                'priority'      => $o->priority,
                'next_action'   => $o->next_action,
                'next_action_at'=> $o->next_action_at,
                'entity_id'     => $o->entity_id,
            ]),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }
}
