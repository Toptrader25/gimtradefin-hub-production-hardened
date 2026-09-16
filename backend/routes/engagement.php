<?php
use Illuminate\Support\Facades\Route;
use App\Services\CommercialEngagementService;
use Illuminate\Http\Request;

Route::prefix('v1/engagement')->middleware(['auth:sanctum','reviewer','throttle:api'])->group(function(){
 Route::post('/opportunities', function(Request $r,CommercialEngagementService $s){return response()->json(['opportunity_id'=>$s->createOpportunity($r->all())],201);});
 Route::get('/opportunities/{id}', function($id,CommercialEngagementService $s){return response()->json($s->snapshot($id));});
 Route::post('/opportunities/{id}/contacts', function($id,Request $r,CommercialEngagementService $s){return response()->json(['contact_id'=>$s->addContact($id,$r->all())],201);});
 Route::post('/opportunities/{id}/stage', function($id,Request $r,CommercialEngagementService $s){return response()->json($s->changeStage($id,(string)$r->input('stage'),(string)$r->input('actor','system'),(string)$r->input('reason','')));});
 Route::post('/opportunities/{id}/activities', function($id,Request $r,CommercialEngagementService $s){return response()->json(['activity_id'=>$s->logActivity($id,$r->all())],201);});
 Route::post('/opportunities/{id}/messages', function($id,Request $r,CommercialEngagementService $s){return response()->json(['message_id'=>$s->createMessage($id,$r->all())],201);});
 Route::post('/messages/{messageId}/sent', function($messageId,Request $r,CommercialEngagementService $s){$s->markMessageSent($messageId,(string)$r->input('actor','system'));return response()->json(['status'=>'sent']);});
 Route::post('/opportunities/{id}/outcomes', function($id,Request $r,CommercialEngagementService $s){$s->recordOutcome($id,(string)$r->input('outcome'),$r->input('score')===null?null:(int)$r->input('score'),(string)$r->input('notes',''),(string)$r->input('actor','system'));return response()->json($s->snapshot($id));});
 Route::get('/opportunities/{id}/next-actions', function($id,CommercialEngagementService $s){return response()->json(['next_best_actions'=>$s->nextBestActions($id)]);});
});
