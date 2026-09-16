<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Services\LearningGovernanceScaleService;
Route::prefix('v1/learning')->middleware(['auth:sanctum','reviewer','throttle:api'])->group(function(){
 Route::post('/events',function(Request $r,LearningGovernanceScaleService $s){return response()->json(['id'=>$s->recordOutcome($r->all())],201);});
 Route::post('/features',function(Request $r,LearningGovernanceScaleService $s){return response()->json(['id'=>$s->snapshotFeatures($r->all())],201);});
 Route::post('/models',function(Request $r,LearningGovernanceScaleService $s){return response()->json(['id'=>$s->registerModel($r->all())],201);});
 Route::post('/models/{id}/promote',function($id,Request $r,LearningGovernanceScaleService $s){$s->promoteModel($id,(string)$r->input('actor','system'),$r->input('approval',[]));return response()->json(['status'=>'production']);});
 Route::post('/predictions',function(Request $r,LearningGovernanceScaleService $s){return response()->json(['id'=>$s->recordPrediction($r->all())],201);});
 Route::post('/predictions/{id}/outcome',function($id,Request $r,LearningGovernanceScaleService $s){$s->attachPredictionOutcome($id,(string)$r->input('label'),$r->input('value'));return response()->json(['status'=>'linked']);});
 Route::post('/drift-alerts',function(Request $r,LearningGovernanceScaleService $s){return response()->json(['id'=>$s->createDriftAlert($r->all())],201);});
 Route::get('/quality',function(LearningGovernanceScaleService $s){return response()->json($s->qualityCheck());});
 Route::post('/governance/{policyKey}/decision',function($policyKey,Request $r,LearningGovernanceScaleService $s){return response()->json($s->governanceDecision($policyKey,$r->all()));});
 Route::post('/experiments/{experimentKey}/assign',function($experimentKey,Request $r,LearningGovernanceScaleService $s){return response()->json(['assignment_id'=>$s->assignExperiment($experimentKey,(string)$r->input('subject_type'),(string)$r->input('subject_id'))]);});
});
