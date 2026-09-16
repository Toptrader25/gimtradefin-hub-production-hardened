<?php
use Illuminate\Support\Facades\Route;
use App\Services\CommercialIntentService;

Route::prefix('v1/intent')->middleware(['auth:sanctum','reviewer','throttle:api'])->group(function(){
 Route::get('/taxonomy', function(){ return response()->json(['signals'=>CommercialIntentService::signals(),'version'=>'1.0']); });
 Route::post('/candidates/{candidateId}/assess', function(string $candidateId, CommercialIntentService $service){ return response()->json($service->assessCandidate($candidateId)); });
 Route::get('/candidates/{candidateId}/events', function(string $candidateId){ return response()->json(DB::table('intent_events')->where('subject_type','candidate')->where('subject_id',$candidateId)->orderByDesc('occurred_at')->get()); });
 Route::get('/candidates/{candidateId}/latest', function(string $candidateId){ return response()->json(DB::table('intent_assessments')->where('subject_type','candidate')->where('subject_id',$candidateId)->orderByDesc('calculated_at')->first()); });
 Route::post('/entities/{entityId}/assess', function(string $entityId, CommercialIntentService $service){ return response()->json($service->assessEntity($entityId)); });
 // Added: the package had this for risk (trust_risk.php) but not intent —
 // without it, an entity's intent panel would have no way to load its
 // last assessment, only ever able to trigger a new one.
 Route::get('/entities/{entityId}/latest', function(string $entityId){ return response()->json(DB::table('intent_assessments')->where('subject_type','entity')->where('subject_id',$entityId)->orderByDesc('calculated_at')->first()); });
});
