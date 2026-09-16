<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCompanyController extends Controller
{
    /**
     * GET /api/v1/admin/companies
     * Unlike the public directory, sees every company regardless of
     * whether they have a published opportunity yet.
     */
    public function index(Request $request)
    {
        $query = Company::query()->withCount('opportunities');

        if ($request->filled('verification_status')) {
            $query->where('verification_status', $request->string('verification_status'));
        }

        $paginated = $query->orderByDesc('created_at')->paginate(20);

        return response()->json([
            'data' => $paginated->getCollection()->map(fn ($c) => [
                'id'                    => $c->id,
                'name'                  => $c->name,
                'country'               => $c->country,
                'role'                  => $c->role,
                'verification_status'   => $c->verification_status,
                'opportunities_count'   => $c->opportunities_count,
                'created_at'            => $c->created_at->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/admin/companies/{id}
     * The reviewer equivalent of the public company profile — shows
     * EVERY opportunity regardless of status (public profile only
     * shows published ones), plus the description/markets/products
     * fields for context when deciding on verification.
     */
    public function show(int $id)
    {
        $company = Company::withCount('opportunities')->findOrFail($id);

        $allOpportunities = $company->opportunities()->orderByDesc('created_at')->get();

        return response()->json([
            'id'                    => $company->id,
            'name'                  => $company->name,
            'country'               => $company->country,
            'industry'              => $company->industry,
            'role'                  => $company->role,
            'description'           => $company->description,
            'website'               => $company->website,
            'markets'               => $company->markets ?? [],
            'products'              => $company->products ?? [],
            'verification_status'   => $company->verification_status,
            'verified_at'           => $company->verified_at?->toIso8601String(),
            'opportunities_count'   => $company->opportunities_count,
            'created_at'            => $company->created_at->toIso8601String(),
            'opportunities' => $allOpportunities->map(fn ($o) => [
                'id'       => $o->id,
                'title'    => $o->title,
                'category' => $o->category,
                'status'   => $o->status,
                'country'  => $o->country,
            ]),
        ]);
    }

    /**
     * PATCH /api/v1/admin/companies/{id}/verify
     * Same audit-log principle as opportunity status changes.
     */
    public function updateVerification(Request $request, int $id)
    {
        $validated = $request->validate([
            'verification_status' => ['required', Rule::in(['unknown', 'pending', 'verified'])],
        ]);

        $company = Company::findOrFail($id);
        $old     = $company->verification_status;
        $new     = $validated['verification_status'];

        $company->verification_status = $new;
        if ($new === 'verified') {
            $company->verified_at = now();
            $company->verified_by = $request->user()->id;
        }
        $company->save();

        AuditLog::create([
            'user_id'        => $request->user()->id,
            'auditable_type' => Company::class,
            'auditable_id'   => $company->id,
            'action'         => 'verification_changed',
            'changes'        => ['from' => $old, 'to' => $new],
        ]);

        return response()->json(['status' => 'updated', 'verification_status' => $new]);
    }
}
