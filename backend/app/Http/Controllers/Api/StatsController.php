<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Opportunity;

class StatsController extends Controller
{
    /**
     * GET /api/v1/stats
     *
     * Every number here is a real COUNT() against published data — none
     * of it is placeholder copy. This exists specifically to replace the
     * "340+ / 58 / 100%" figures that were leftover static text from the
     * old WordPress design and were never actually true of this platform.
     * If there's nothing published yet, these numbers are honestly zero.
     */
    public function index()
    {
        $published = Opportunity::published();

        return response()->json([
            'published_opportunities' => (clone $published)->count(),
            'countries' => (clone $published)->distinct('country')->count('country'),
            'companies' => Company::whereHas('opportunities', fn ($q) => $q->where('status', 'published'))->count(),
            'verified_companies' => Company::where('verification_status', 'verified')->count(),
            'by_category' => [
                'buying'      => (clone $published)->where('category', 'buying')->count(),
                'selling'     => (clone $published)->where('category', 'selling')->count(),
                'partnership' => (clone $published)->where('category', 'partnership')->count(),
            ],
        ]);
    }
}
