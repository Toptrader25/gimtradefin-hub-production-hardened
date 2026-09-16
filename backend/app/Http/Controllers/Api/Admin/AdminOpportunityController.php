<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Opportunity;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminOpportunityController extends Controller
{
    /**
     * GET /api/v1/admin/opportunities
     * Unlike the public endpoint, this sees EVERY status — that's the
     * whole point of a review queue.
     */
    public function index(Request $request)
    {
        $query = Opportunity::query()->with('company')->withCount('evidence');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        $paginated = $query->orderByDesc('created_at')->paginate(20);

        return response()->json([
            'data' => $paginated->getCollection()->map(fn ($o) => $this->transformSummary($o)),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/admin/opportunities/{id}
     * Includes submitter contact info and full evidence — deliberately
     * NEVER exposed on the public OpportunityController. This is the
     * one place in the system a reviewer can see who actually submitted
     * something, needed to follow up or ask for more evidence.
     */
    public function show(int $id)
    {
        $opportunity = Opportunity::with(['company', 'evidence', 'verifiedBy'])->findOrFail($id);

        return response()->json([
            ...$this->transformSummary($opportunity),
            'quantity'          => $opportunity->quantity,
            'preferred_origin'  => $opportunity->preferred_origin,
            'payment_terms'     => $opportunity->payment_terms,
            'description'       => $opportunity->description,
            'submitted_by_name'    => $opportunity->submitted_by_name,
            'submitted_by_email'   => $opportunity->submitted_by_email,
            'submitted_by_company' => $opportunity->submitted_by_company,
            'submitted_by_phone'   => $opportunity->submitted_by_phone,
            // Individual score dimensions — needed to pre-populate the
            // score editor. transformSummary() only includes the
            // rolled-up overall_score, which is all the public/list
            // views need, but this detail endpoint needs the full set.
            'commercial_intent_score'  => $opportunity->commercial_intent_score,
            'source_reliability_score' => $opportunity->source_reliability_score,
            'evidence_strength_score'  => $opportunity->evidence_strength_score,
            'recency_score'            => $opportunity->recency_score,
            'company_confidence_score' => $opportunity->company_confidence_score,
            'match_potential_score'    => $opportunity->match_potential_score,
            'evidence' => $opportunity->evidence->map(fn ($e) => [
                'id'                => $e->id,
                'evidence_type'     => $e->evidence_type,
                'excerpt'           => $e->excerpt,
                'original_url'      => $e->original_url,
                'discovered_at'     => $e->discovered_at?->toIso8601String(),
                'source_confidence' => $e->source_confidence,
            ]),
            'verified_by' => $opportunity->verifiedBy ? [
                'id'   => $opportunity->verifiedBy->id,
                'name' => $opportunity->verifiedBy->name,
            ] : null,
            'verified_at' => $opportunity->verified_at?->toIso8601String(),
        ]);
    }

    /**
     * PATCH /api/v1/admin/opportunities/{id}/status
     *
     * The ONLY place in the entire system an opportunity's status can
     * change. This is where "AI analyzes, a person verifies" is actually
     * enforced in code — this endpoint requires auth:sanctum + the
     * reviewer role middleware, and nothing else in the codebase writes
     * to this column. Every change is audit-logged with who, what, when,
     * and optional notes — see architecture doc section 21.
     */
    public function updateStatus(Request $request, int $id)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['discovered', 'reviewing', 'verified', 'published', 'expired', 'rejected'])],
            'notes'  => ['nullable', 'string', 'max:2000'],
        ]);

        $opportunity = Opportunity::findOrFail($id);
        $oldStatus   = $opportunity->status;
        $newStatus   = $validated['status'];

        $opportunity->status = $newStatus;

        if (in_array($newStatus, ['verified', 'published'], true)) {
            $opportunity->verified_by_user_id = $request->user()->id;
            $opportunity->verified_at = now();
        }

        if ($newStatus === 'published' && ! $opportunity->published_at) {
            $opportunity->published_at = now();
        }

        $opportunity->save();

        AuditLog::create([
            'user_id'         => $request->user()->id,
            'auditable_type'  => Opportunity::class,
            'auditable_id'    => $opportunity->id,
            'action'          => 'status_changed',
            'changes'         => [
                'from'  => $oldStatus,
                'to'    => $newStatus,
                'notes' => $validated['notes'] ?? null,
            ],
        ]);

        // Only notify on statuses that actually mean something to the
        // submitter — "reviewing" is internal churn, not news.
        if (in_array($newStatus, ['published', 'rejected'], true) && $opportunity->submitted_by_email) {
            $notifications = app(NotificationService::class);
            $subject = $newStatus === 'published'
                ? 'Your GiMtradefin submission is live'
                : 'Update on your GiMtradefin submission';
            $body = $newStatus === 'published'
                ? "Good news — \"{$opportunity->title}\" has been reviewed and is now published on GiMtradefin.\n\nView it: " . rtrim(env('FRONTEND_URL', ''), '/') . "/opportunities/{$opportunity->id}"
                : "Your submission \"{$opportunity->title}\" was reviewed and was not accepted for publication."
                  . ($validated['notes'] ?? null ? "\n\nReviewer notes: {$validated['notes']}" : '');

            if ($opportunity->submitted_by_user_id) {
                $user = \App\Models\User::find($opportunity->submitted_by_user_id);
                if ($user) {
                    $notifications->notifyUser($user, 'opportunity_status_changed', [
                        'opportunity_id' => $opportunity->id,
                        'status'         => $newStatus,
                    ], $subject, $body);
                }
            } else {
                $notifications->notifyEmailOnly($opportunity->submitted_by_email, $subject, $body);
            }
        }

        return response()->json(['status' => 'updated', 'new_status' => $newStatus]);
    }

    /**
     * PATCH /api/v1/admin/opportunities/{id}/scores
     * Closes a real gap: the schema has had commercial_intent_score,
     * source_reliability_score, evidence_strength_score, etc. since
     * Phase 2, but there was never anywhere for a human to actually
     * enter them — a reviewer could mark something "Verified" and every
     * score would stay silently blank. This is a manual stopgap until
     * the AI scoring engine exists (see doc section 15); a human typing
     * a defensible number is more honest than leaving it empty forever.
     */
    public function updateScores(Request $request, int $id)
    {
        $validated = $request->validate([
            'commercial_intent_score'  => ['nullable', 'integer', 'min:0', 'max:100'],
            'source_reliability_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'evidence_strength_score'  => ['nullable', 'integer', 'min:0', 'max:100'],
            'recency_score'            => ['nullable', 'integer', 'min:0', 'max:100'],
            'company_confidence_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'match_potential_score'    => ['nullable', 'integer', 'min:0', 'max:100'],
            'overall_score'            => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $opportunity = Opportunity::findOrFail($id);
        $before = $opportunity->only(array_keys($validated));

        $opportunity->fill($validated);
        $opportunity->save();

        AuditLog::create([
            'user_id'        => $request->user()->id,
            'auditable_type' => Opportunity::class,
            'auditable_id'   => $opportunity->id,
            'action'         => 'scores_updated',
            'changes'        => ['from' => $before, 'to' => $validated],
        ]);

        return response()->json(['status' => 'updated']);
    }

    private function transformSummary(Opportunity $o): array
    {
        return [
            'id'             => $o->id,
            'title'          => $o->title,
            'category'       => $o->category,
            'status'         => $o->status,
            'country'        => $o->country,
            'overall_score'  => $o->overall_score,
            'evidence_count' => $o->evidence_count ?? null,
            'company'        => $o->company ? ['id' => $o->company->id, 'name' => $o->company->name] : null,
            'created_at'     => $o->created_at->toIso8601String(),
        ];
    }
}
