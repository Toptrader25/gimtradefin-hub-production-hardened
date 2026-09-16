<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;

class AdminStatsController extends Controller
{
    /**
     * GET /api/v1/admin/stats
     * The dashboard's home screen numbers — see doc section 21:
     * "NEW COMMERCIAL SIGNALS, REQUIRES VERIFICATION, VERIFIED,
     * PUBLISHED, EXPIRED..."
     */
    public function index()
    {
        return response()->json([
            'by_status' => [
                'discovered' => Opportunity::where('status', 'discovered')->count(),
                'reviewing'  => Opportunity::where('status', 'reviewing')->count(),
                'verified'   => Opportunity::where('status', 'verified')->count(),
                'published'  => Opportunity::where('status', 'published')->count(),
                'expired'    => Opportunity::where('status', 'expired')->count(),
                'rejected'   => Opportunity::where('status', 'rejected')->count(),
            ],
            'pending_review' => Opportunity::whereIn('status', ['discovered', 'reviewing'])->count(),
        ]);
    }
}
