<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Services\MultilingualIntelligenceService;

Route::prefix('v1/multilingual')->middleware(['auth:sanctum','reviewer','throttle:api'])->group(function(){
 Route::post('/analyze', function(Request $request, MultilingualIntelligenceService $service){ $data=$request->validate(['asset_type'=>'required|string|max:40','asset_id'=>'required|string|max:100','text'=>'required|string','language'=>'nullable|string|max:16']); return response()->json($service->analyze($data['asset_type'],$data['asset_id'],$data['text'],$data['language']??null)); });
 Route::post('/translate/{assetId}', function(Request $request,string $assetId,MultilingualIntelligenceService $service){ $data=$request->validate(['target_language'=>'nullable|string|max:16']); $r=$service->translate($assetId,$data['target_language']??config('multilingual.default_target','en')); return $r?response()->json($r):response()->json(['message'=>'Translation unavailable or asset not found.'],404); });
});
