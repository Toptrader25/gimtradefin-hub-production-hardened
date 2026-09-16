<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    /**
     * GET /api/v1/companies
     *
     * Public directory. Shows every company that has at least one
     * PUBLISHED opportunity — a company with only unreviewed submissions
     * shouldn't appear in a public directory yet, same principle as
     * opportunities themselves never being public before verification.
     */
    public function index(Request $request)
    {
        $query = Company::query()
            ->whereHas('opportunities', fn ($q) => $q->where('status', 'published'))
            ->withCount([
                'opportunities as published_opportunities_count' => fn ($q) => $q->where('status', 'published'),
            ]);

        if ($request->filled('country')) {
            $query->where('country', $request->string('country'));
        }

        if ($request->filled('industry')) {
            $query->where('industry', $request->string('industry'));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->string('search') . '%');
        }

        $paginated = $query->orderByDesc('published_opportunities_count')->paginate(20);

        return response()->json([
            'data' => $paginated->getCollection()->map(fn ($c) => $this->transformSummary($c)),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/companies/{id}
     *
     * Full Company Intelligence Profile — see architecture doc section 13.
     * Includes the company's published opportunities. Unpublished/pending
     * opportunities are never included here, even for the company's own
     * profile — this is a public endpoint, not an owner dashboard (that
     * requires auth, which doesn't exist yet).
     */
    public function show(int $id)
    {
        $company = Company::withCount([
            'opportunities as published_opportunities_count' => fn ($q) => $q->where('status', 'published'),
            'opportunities as buying_opportunities_count' => fn ($q) => $q->where('status', 'published')->where('category', 'buying'),
            'opportunities as selling_opportunities_count' => fn ($q) => $q->where('status', 'published')->where('category', 'selling'),
            'opportunities as partnership_opportunities_count' => fn ($q) => $q->where('status', 'published')->where('category', 'partnership'),
        ])->findOrFail($id);

        $publishedOpportunities = $company->opportunities()
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->get();

        return response()->json([
            ...$this->transformSummary($company),
            'description' => $company->description,
            'website'     => $company->website,
            'markets'     => $company->markets ?? [],
            'products'    => $company->products ?? [],
            'counts' => [
                'buying'      => $company->buying_opportunities_count,
                'selling'     => $company->selling_opportunities_count,
                'partnership' => $company->partnership_opportunities_count,
            ],
            'opportunities' => $publishedOpportunities->map(fn ($opp) => [
                'id'           => $opp->id,
                'title'        => $opp->title,
                'category'     => $opp->category,
                'country'      => $opp->country,
                'overall_score'=> $opp->overall_score,
                'published_at' => $opp->published_at?->toIso8601String(),
            ]),
        ]);
    }

    private function transformSummary(Company $company): array
    {
        return [
            'id'                            => $company->id,
            'name'                          => $company->name,
            'country'                       => $company->country,
            'industry'                      => $company->industry,
            'role'                          => $company->role,
            'verification_status'          => $company->verification_status,
            'published_opportunities_count' => $company->published_opportunities_count,
        ];
    }
}
