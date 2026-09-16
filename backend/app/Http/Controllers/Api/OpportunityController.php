<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OpportunityController extends Controller
{
    /**
     * GET /api/v1/opportunities
     *
     * Public endpoint — ONLY ever returns published opportunities.
     * This is the single most important line of defense for section 11
     * ("nothing should automatically become a public verified lead"):
     * it is enforced here in the query itself, not left to the frontend
     * to filter correctly.
     */
    public function index(Request $request)
    {
        $query = Opportunity::query()->published()->with('company');

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        if ($request->filled('country')) {
            $query->where('country', $request->string('country'));
        }

        if ($request->filled('search')) {
            $term = $request->string('search');
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%")
                  ->orWhere('quantity', 'like', "%{$term}%")
                  ->orWhere('preferred_origin', 'like', "%{$term}%");
            });
        }

        $perPage = 12;
        $paginated = $query->orderByDesc('published_at')->paginate($perPage);

        return response()->json([
            'data' => $paginated->getCollection()->map(fn ($opp) => $this->transform($opp)),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/opportunities/{id}
     * Also published-only — a direct-link guess at an unpublished ID
     * must not leak it.
     */
    public function show(int $id)
    {
        $opportunity = Opportunity::published()->with(['company', 'evidence'])->findOrFail($id);

        return response()->json($this->transform($opportunity, includeEvidence: true));
    }

    /**
     * GET /api/v1/opportunities/{id}/matches
     *
     * Rule-based, not AI-based — the AI Engine (doc Phase 5) doesn't
     * exist yet, and Lead Hunter's own matching engine operates on its
     * internal entities/candidates, not this app's Opportunity rows, so
     * there's no existing match data to surface here either. This is a
     * deliberately simple, explainable heuristic: opposite category
     * (buying↔selling, partnership↔partnership) plus same country,
     * scored by how many results share the exact country. Honest and
     * useful now; swappable for a real matching engine later without
     * changing the API contract.
     */
    public function matches(int $id)
    {
        $opportunity = Opportunity::published()->findOrFail($id);

        $oppositeCategory = match ($opportunity->category) {
            'buying'      => 'selling',
            'selling'     => 'buying',
            'partnership' => 'partnership',
            default       => null,
        };

        $matches = Opportunity::published()
            ->where('id', '!=', $opportunity->id)
            ->where('category', $oppositeCategory)
            ->orderByRaw('CASE WHEN country = ? THEN 0 ELSE 1 END', [$opportunity->country])
            ->orderByDesc('published_at')
            ->limit(5)
            ->with('company')
            ->get();

        return response()->json([
            'data' => $matches->map(fn ($m) => [
                'id'              => $m->id,
                'title'           => $m->title,
                'category'        => $m->category,
                'country'         => $m->country,
                'same_country'    => $m->country === $opportunity->country,
                'company'         => $m->company ? ['id' => $m->company->id, 'name' => $m->company->name] : null,
            ]),
        ]);
    }

    private function transform(Opportunity $opp, bool $includeEvidence = false): array
    {
        $data = [
            'id'                       => $opp->id,
            'title'                    => $opp->title,
            'category'                 => $opp->category,
            'status'                   => $opp->status,
            'description'              => $opp->description,
            'quantity'                 => $opp->quantity,
            'country'                  => $opp->country,
            'preferred_origin'         => $opp->preferred_origin,
            'payment_terms'            => $opp->payment_terms,
            'commercial_intent_score'  => $opp->commercial_intent_score,
            'source_reliability_score' => $opp->source_reliability_score,
            'evidence_strength_score'  => $opp->evidence_strength_score,
            'overall_score'            => $opp->overall_score,
            'company'                  => $opp->company ? [
                'id'                   => $opp->company->id,
                'name'                 => $opp->company->name,
                'country'              => $opp->company->country,
                'industry'             => $opp->company->industry,
                'role'                 => $opp->company->role,
                'verification_status'  => $opp->company->verification_status,
            ] : null,
            'published_at' => $opp->published_at?->toIso8601String(),
            'created_at'   => $opp->created_at->toIso8601String(),
        ];

        // Evidence is intentionally only exposed on the single-opportunity
        // view, not the list view — per section 12, this is what lets a
        // reviewer (or eventually the public UI) answer "where did this
        // come from", without bloating every list response.
        if ($includeEvidence) {
            $data['evidence'] = $opp->evidence->map(fn ($e) => [
                'original_url'  => $e->original_url,
                'excerpt'       => $e->excerpt,
                'evidence_type' => $e->evidence_type,
                'discovered_at' => $e->discovered_at?->toIso8601String(),
            ]);
        }

        return $data;
    }
}
