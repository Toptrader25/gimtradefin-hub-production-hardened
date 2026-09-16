<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Services\CommercialIntelligenceService;
Route::prefix('v1/commercial-intelligence')->middleware(['auth:sanctum','reviewer'])->group(function(){
 Route::post('/entities/{id}/profile', function(string $id, CommercialIntelligenceService $s){ return response()->json($s->profileEntity($id)); });
 Route::get('/entities/{id}/profile', function(string $id){ $p=DB::table('entity_commercial_profiles')->where('entity_id',$id)->first(); abort_unless($p,404); return response()->json($p); });
 Route::get('/entities/{id}/facts', function(string $id){ return DB::table('entity_commercial_facts')->where('entity_id',$id)->orderByDesc('observed_at')->paginate(100); });
 Route::get('/entities/{id}/roles', function(string $id){ return DB::table('entity_commercial_roles')->where('entity_id',$id)->orderByDesc('calculated_at')->limit(100)->get(); });
 Route::get('/entities/{id}/relationships', function(string $id){ return DB::table('entity_relationships')->where(function($q)use($id){$q->where('from_entity_id',$id)->orWhere('to_entity_id',$id);})->orderByDesc('observed_at')->paginate(100); });
});
