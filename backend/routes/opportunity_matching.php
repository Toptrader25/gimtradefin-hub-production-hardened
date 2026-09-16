<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Services\OpportunityMatchingService;
Route::prefix('v1/matching')->middleware(['auth:sanctum','reviewer','throttle:api'])->group(function(){
 Route::get('/entity/{entityId}', function(Request $r, OpportunityMatchingService $svc, string $entityId){ return response()->json($svc->matchEntity($entityId,(int)$r->query('limit',25))); });
 Route::get('/{matchId}/explain', function(OpportunityMatchingService $svc,string $matchId){ return response()->json($svc->explain($matchId)); });
 Route::post('/{matchId}/feedback', function(Request $r,string $matchId){ $id=\Illuminate\Support\Str::uuid()->toString(); \Illuminate\Support\Facades\DB::table('match_feedback')->insert(['id'=>$id,'match_id'=>$matchId,'decision'=>$r->input('decision','review'),'actual_quality'=>$r->input('actual_quality'),'notes'=>$r->input('notes'),'reviewer'=>$r->input('reviewer'),'created_at'=>now()]); return response()->json(['id'=>$id,'status'=>'recorded']); });
});
