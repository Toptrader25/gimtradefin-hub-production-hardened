<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Evidence;
use App\Models\Opportunity;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OpportunitySubmissionController extends Controller
{
    /**
     * POST /api/v1/opportunities
     *
     * Public endpoint — anyone can submit an opportunity, same as the
     * WordPress Submit Lead form before it. Two rules that must never
     * be broken here, both straight from the architecture doc:
     *
     * 1. Status is ALWAYS "discovered", never "published". A direct
     *    submission is not verification — it still goes through the
     *    same human-review pipeline as anything a source connector
     *    finds. See section 11.
     *
     * 2. Every opportunity carries evidence, even a direct submission.
     *    We record the submission itself as the evidence — "self-reported
     *    by submitter, not yet independently verified" — rather than
     *    silently having no evidence trail for these. See section 12.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'             => ['required', 'string', 'max:255'],
            'category'          => ['required', Rule::in(['buying', 'selling', 'partnership'])],
            'description'       => ['required', 'string', 'max:5000'],
            'quantity'          => ['nullable', 'string', 'max:255'],
            'country'           => ['required', 'string', 'max:255'],
            'preferred_origin'  => ['nullable', 'string', 'max:255'],
            'payment_terms'     => ['nullable', 'string', 'max:255'],
            'submitted_by_name'    => ['required', 'string', 'max:255'],
            'submitted_by_email'   => ['required', 'email', 'max:255'],
            'submitted_by_company' => ['nullable', 'string', 'max:255'],
            'submitted_by_phone'   => ['nullable', 'string', 'max:255'],
            // Honeypot — same pattern as the WordPress form. Real users
            // never fill this; if it's present, silently drop the request.
            'website' => ['nullable', 'string', 'max:0'],
        ]);

        // Find-or-create the Company record, matched on a NORMALIZED name
        // (case/whitespace/legal-suffix insensitive) within the same
        // country. This is still a placeholder for real entity resolution
        // (see doc section 6 — that belongs in the Intelligence Engine,
        // likely fuzzy/embedding-based matching over the full company
        // corpus) but it closes the most common failure mode: "ABC
        // Trading Ltd" vs "ABC Trading Limited" vs "abc trading" no
        // longer become three separate companies.
        $company = null;
        if (! empty($validated['submitted_by_company'])) {
            $company = $this->findOrCreateCompany($validated['submitted_by_company'], $validated['country'], $validated['category']);
        }

        $opportunity = Opportunity::create([
            'title'                => $validated['title'],
            'category'             => $validated['category'],
            'description'          => $validated['description'],
            'quantity'             => $validated['quantity'] ?? null,
            'country'              => $validated['country'],
            'preferred_origin'     => $validated['preferred_origin'] ?? null,
            'payment_terms'        => $validated['payment_terms'] ?? null,
            'status'               => 'discovered',
            'company_id'           => $company?->id,
            // Submission stays public (no auth required) — but if the
            // request DOES carry a valid Bearer token, we still record
            // who it was. This is what lets a signed-in user's "My
            // Dashboard" show their own submissions later; without it,
            // there'd be no way to know which opportunities are theirs.
            //
            // IMPORTANT: must specify the 'sanctum' guard explicitly.
            // This route has no auth:sanctum middleware (submission is
            // public), so $request->user() alone would resolve the
            // *default* guard ('web', session-based) and always return
            // null here, even with a valid Bearer token. Querying the
            // sanctum guard directly still checks the token — it just
            // doesn't reject the request if there isn't one.
            'submitted_by_user_id' => $request->user('sanctum')?->id,
            'submitted_by_name'    => $validated['submitted_by_name'],
            'submitted_by_email'   => $validated['submitted_by_email'],
            'submitted_by_company' => $validated['submitted_by_company'] ?? null,
            'submitted_by_phone'   => $validated['submitted_by_phone'] ?? null,
        ]);

        Evidence::create([
            'opportunity_id'   => $opportunity->id,
            'source_id'        => null, // no connector — this is a direct submission
            'discovered_at'    => now(),
            'evidence_type'    => 'direct_submission',
            'excerpt'          => "Self-reported by {$validated['submitted_by_name']} ({$validated['submitted_by_email']}). Not yet independently verified.",
            'source_confidence'=> null, // deliberately unscored until a reviewer checks it
        ]);

        // Alert every admin/reviewer — closes what was previously a TODO.
        // Uses the same NotificationService as the other two trigger
        // points (status change, new enquiry), so all three stay
        // consistent rather than three different half-implementations.
        app(NotificationService::class)->notifyReviewers(
            'new_submission',
            ['opportunity_id' => $opportunity->id, 'category' => $opportunity->category],
            'New submission needs review — GiMtradefin',
            "A new opportunity was submitted and needs review:\n\n\"{$opportunity->title}\" ({$opportunity->category}, {$opportunity->country})\n\nReview it: " . rtrim(env('FRONTEND_URL', ''), '/') . "/admin/opportunities/{$opportunity->id}"
        );

        return response()->json([
            'status' => 'received',
            'id'     => $opportunity->id,
            'message'=> 'Submitted for review. This will not appear publicly until verified.',
        ], 201);
    }

    /**
     * Matches an existing company by normalized name within the same
     * country, or creates a new one. Normalization strips legal suffixes
     * (Ltd, Limited, LLC, Inc, Corp, Sdn Bhd, GmbH, Pte, Co) and collapses
     * whitespace/case — enough to catch the common real-world variants
     * without needing a fuzzy-matching library or an extra DB column.
     */
    private function findOrCreateCompany(string $rawName, string $country, string $category): Company
    {
        $target = self::normalizeCompanyName($rawName);

        $existing = Company::where('country', $country)->get()
            ->first(fn ($c) => self::normalizeCompanyName($c->name) === $target);

        if ($existing) {
            return $existing;
        }

        return Company::create([
            'name'                 => $rawName,
            'country'              => $country,
            'role'                 => ucfirst($category) . ' — self-reported',
            'verification_status'  => 'unknown',
        ]);
    }

    private static function normalizeCompanyName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/\s+/', ' ', $name);
        $suffixes = ['ltd', 'limited', 'llc', 'inc', 'incorporated', 'corp', 'corporation',
                     'sdn bhd', 'gmbh', 'pte ltd', 'pte', 'co', 'company', 'plc'];
        foreach ($suffixes as $suffix) {
            $name = preg_replace('/\b' . preg_quote($suffix, '/') . '\b\.?$/', '', $name);
        }
        return trim($name, " \t\n\r\0\x0B.,-");
    }
}
