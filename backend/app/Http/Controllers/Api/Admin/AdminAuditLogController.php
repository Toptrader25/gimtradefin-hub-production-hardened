<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Opportunity;
use Illuminate\Http\Request;

class AdminAuditLogController extends Controller
{
    /**
     * GET /api/v1/admin/audit-log
     * Global recent-activity feed for the dashboard — every status
     * change and verification decision across the whole platform,
     * newest first. This is what makes the audit trail actually mean
     * something: it existed in the database since Phase 2, but until
     * now nothing ever displayed it.
     */
    public function index(Request $request)
    {
        $entries = AuditLog::with('user:id,name')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json(['data' => $entries->map(fn ($e) => $this->transform($e))]);
    }

    /**
     * GET /api/v1/admin/opportunities/{id}/audit-log
     */
    public function forOpportunity(int $id)
    {
        Opportunity::findOrFail($id); // 404 if it doesn't exist

        $entries = AuditLog::with('user:id,name')
            ->where('auditable_type', Opportunity::class)
            ->where('auditable_id', $id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $entries->map(fn ($e) => $this->transform($e))]);
    }

    /**
     * GET /api/v1/admin/companies/{id}/audit-log
     */
    public function forCompany(int $id)
    {
        Company::findOrFail($id);

        $entries = AuditLog::with('user:id,name')
            ->where('auditable_type', Company::class)
            ->where('auditable_id', $id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $entries->map(fn ($e) => $this->transform($e))]);
    }

    private function transform(AuditLog $entry): array
    {
        return [
            'id'             => $entry->id,
            'action'         => $entry->action,
            'changes'        => $entry->changes,
            'auditable_type' => class_basename($entry->auditable_type),
            'auditable_id'   => $entry->auditable_id,
            'user'           => $entry->user ? ['id' => $entry->user->id, 'name' => $entry->user->name] : null,
            'created_at'     => $entry->created_at->toIso8601String(),
        ];
    }
}
