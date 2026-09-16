<?php

use App\Http\Controllers\Api\OpportunityController;
use App\Http\Controllers\Api\OpportunitySubmissionController;
use App\Http\Controllers\Api\EnquiryController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\Admin\AdminOpportunityController;
use App\Http\Controllers\Api\Admin\AdminCompanyController;
use App\Http\Controllers\Api\Admin\AdminStatsController;
use App\Http\Controllers\Api\Admin\AdminAuditLogController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Admin\AdminLeadHunterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — v1
|--------------------------------------------------------------------------
| Public, read-only endpoints only. Admin/verification/matching endpoints
| get their own auth-protected route group in later phases (see doc
| section 23, phases 6-7) — deliberately not built yet so we don't ship
| unauthenticated write access to anything.
|
| Two deliberate exceptions, both public by design (same as the old
| WordPress Submit Lead form):
|   - POST /opportunities            → creates status="discovered" only,
|     never published. See OpportunitySubmissionController.
|   - POST /opportunities/{id}/enquiries → only writes against an
|     already-published opportunity. See EnquiryController.
*/

Route::prefix('v1')->group(function () {
    Route::get('/health', fn () => response()->json(['status' => 'ok', 'service' => 'gimtradefin-hub-api']));

    Route::get('/opportunities', [OpportunityController::class, 'index']);
    Route::get('/opportunities/{id}', [OpportunityController::class, 'show']);
    Route::get('/opportunities/{id}/matches', [OpportunityController::class, 'matches']);
    Route::post('/opportunities', [OpportunitySubmissionController::class, 'store'])->middleware('throttle:10,1');
    Route::post('/opportunities/{id}/enquiries', [EnquiryController::class, 'store'])->middleware('throttle:10,1');

    Route::get('/companies', [CompanyController::class, 'index']);
    Route::get('/companies/{id}', [CompanyController::class, 'show']);

    Route::get('/stats', [StatsController::class, 'index']);

    // Auth — Bearer token via Sanctum, not cookie-based SPA auth (the
    // Next.js frontend and this API are separately deployed, so token
    // auth is the simpler, more robust fit). throttle:5,1 = 5 attempts
    // per minute, to blunt brute-force and credential-stuffing attempts.
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Any signed-in user — their own data only, scoped server-side
        // by $request->user()->id in MeController, never by anything
        // client-supplied.
        Route::get('/me/opportunities', [MeController::class, 'opportunities']);
        Route::get('/me/enquiries', [MeController::class, 'enquiries']);
        Route::patch('/me/password', [MeController::class, 'changePassword']);

        Route::get('/me/notifications', [NotificationController::class, 'index']);
        Route::patch('/me/notifications/read-all', [NotificationController::class, 'markAllRead']);
        Route::patch('/me/notifications/{id}/read', [NotificationController::class, 'markRead']);
    });

    // Admin/reviewer only — the 'reviewer' middleware is the real
    // security boundary here, not just a hidden frontend nav link.
    Route::middleware(['auth:sanctum', 'reviewer'])->prefix('admin')->group(function () {
        Route::get('/stats', [AdminStatsController::class, 'index']);
        Route::get('/audit-log', [AdminAuditLogController::class, 'index']);

        Route::get('/opportunities', [AdminOpportunityController::class, 'index']);
        Route::get('/opportunities/{id}', [AdminOpportunityController::class, 'show']);
        Route::patch('/opportunities/{id}/status', [AdminOpportunityController::class, 'updateStatus']);
        Route::patch('/opportunities/{id}/scores', [AdminOpportunityController::class, 'updateScores']);
        Route::get('/opportunities/{id}/audit-log', [AdminAuditLogController::class, 'forOpportunity']);

        Route::get('/companies', [AdminCompanyController::class, 'index']);
        Route::get('/companies/{id}', [AdminCompanyController::class, 'show']);
        Route::patch('/companies/{id}/verify', [AdminCompanyController::class, 'updateVerification']);
        Route::get('/companies/{id}/audit-log', [AdminAuditLogController::class, 'forCompany']);

        Route::post('/lead-hunter/sync', [AdminLeadHunterController::class, 'sync']);
        Route::get('/lead-hunter/candidates', [AdminLeadHunterController::class, 'candidates']);
        Route::get('/lead-hunter/cases', [AdminLeadHunterController::class, 'verificationCases']);
        Route::get('/lead-hunter/engagement-opportunities', [AdminLeadHunterController::class, 'engagementOpportunities']);
    });

    // Admin-only (stricter than reviewer) — granting roles is more
    // sensitive than reviewing content, see EnsureUserIsAdmin.
    Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::patch('/users/{id}/role', [AdminUserController::class, 'updateRole']);
    });
});

/*
|--------------------------------------------------------------------------
| Lead Hunter module routes (merged package)
|--------------------------------------------------------------------------
| Internal intelligence-pipeline routes — source discovery, entity
| resolution, trust/risk, commercial intent, verification, matching,
| engagement. All already auth:sanctum-protected inside these files.
| This is a SEPARATE data model from the /v1/opportunities and
| /v1/companies routes above — see docs/LEAD_HUNTER_DEPLOYMENT_READINESS.md
| and the bridge service that connects verified output back to the
| public-facing Opportunity/Company tables.
*/
require __DIR__.'/entity_resolution.php';
require __DIR__.'/trust_risk.php';
require base_path('routes/commercial_intent.php');
require base_path('routes/commercial_intelligence.php');
require base_path('routes/multilingual.php');
require base_path('routes/opportunity_matching.php');
require base_path('routes/semantic_matching.php');
require __DIR__.'/learning_governance.php';
require base_path('routes/verification.php');
require base_path('routes/engagement.php');
