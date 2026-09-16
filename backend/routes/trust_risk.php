<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Services\TrustRiskService;

Route::prefix('v1/trust')->middleware(['auth:sanctum','reviewer','throttle:api'])->group(function(){
    Route::post('/candidates/{id}/assess', function(string $id, TrustRiskService $s){return response()->json($s->assessCandidate($id));});
    Route::post('/entities/{id}/assess', function(string $id, TrustRiskService $s){return response()->json($s->assessEntity($id));});
    Route::get('/candidates/{id}/latest', function(string $id){return response()->json(DB::table('risk_assessments')->where('subject_type','candidate')->where('subject_id',$id)->latest('calculated_at')->first());});
    Route::get('/entities/{id}/latest', function(string $id){return response()->json(DB::table('risk_assessments')->where('subject_type','entity')->where('subject_id',$id)->latest('calculated_at')->first());});
    Route::get('/reviews', function(){return response()->json(DB::table('risk_review_cases')->where('status','open')->orderByRaw("CASE priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")->latest()->paginate(50));});
    Route::get('/duplicates/{candidateId}', function(string $candidateId){return response()->json(DB::table('duplicate_cluster_members as m')->join('duplicate_clusters as c','c.id','=','m.cluster_id')->where('m.candidate_id',$candidateId)->select('c.*','m.similarity','m.features')->get());});
});
