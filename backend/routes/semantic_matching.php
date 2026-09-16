<?php
use Illuminate\Support\Facades\Route;
use App\Services\SemanticOpportunityService;

Route::prefix('v1/semantic')->middleware(['auth:sanctum','reviewer','throttle:api'])->group(function(){
 Route::post('/entities/{entityId}/profile', function(string $entityId, SemanticOpportunityService $svc){ return response()->json(['entity_id'=>$entityId,'profile'=>$svc->buildEntityProfile($entityId)]); });
});
