<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Services\EntityResolutionService;

Route::prefix('v1/entities')->middleware(['auth:sanctum','reviewer'])->group(function(){
    Route::get('/', function(){
        return DB::table('entities')->orderByDesc('last_seen_at')->paginate(50);
    });
    Route::get('/{id}', function(string $id){
        $entity=DB::table('entities')->where('id',$id)->first(); abort_unless($entity,404);
        $entity->aliases=DB::table('entity_aliases')->where('entity_id',$id)->get();
        $entity->identifiers=DB::table('entity_identifiers')->where('entity_id',$id)->get();
        $entity->observations=DB::table('entity_observations')->where('entity_id',$id)->latest('observed_at')->limit(100)->get();
        $entity->links=DB::table('entity_links')->where('entity_id',$id)->latest('created_at')->limit(100)->get();
        return response()->json($entity);
    });
    Route::post('/resolve/{candidateId}', function(string $candidateId, EntityResolutionService $service){return response()->json($service->resolveCandidate($candidateId));});
    Route::get('/{id}/matches', function(string $id){return DB::table('entity_match_candidates')->where('entity_id',$id)->orderByDesc('score')->paginate(50);});
});
