<?php
use Illuminate\Support\Facades\Route;
use App\Services\VerificationWorkflowService;

Route::prefix('v1/verification')->middleware(['auth:sanctum','reviewer','throttle:api'])->group(function(){
 Route::post('/cases', function(\Illuminate\Http\Request $r, VerificationWorkflowService $s){return response()->json(['case_id'=>$s->createCase($r->all())],201);});
 Route::get('/cases/{id}', function($id, VerificationWorkflowService $s){return response()->json($s->snapshot($id));});
 Route::post('/cases/{id}/start', function($id,\Illuminate\Http\Request $r,VerificationWorkflowService $s){return response()->json($s->start($id,(string)$r->input('reviewer','system')));});
 Route::post('/cases/{id}/checks', function($id,\Illuminate\Http\Request $r,VerificationWorkflowService $s){return response()->json($s->recordCheck($id,(string)$r->input('check_code'),(string)$r->input('status'),$r->input('score')!==null?(int)$r->input('score'):null,$r->input('finding'),$r->input('evidence_refs',[]),(string)$r->input('reviewer','system')));});
 Route::post('/cases/{id}/qualification', function($id,\Illuminate\Http\Request $r,VerificationWorkflowService $s){$s->answerQualification($id,(string)$r->input('question_code'),$r->input('answer'),$r->input('score')!==null?(int)$r->input('score'):null,(string)$r->input('reviewer','system'));return response()->json($s->snapshot($id));});
 Route::post('/cases/{id}/decision', function($id,\Illuminate\Http\Request $r,VerificationWorkflowService $s){return response()->json($s->decide($id,(string)$r->input('decision'),(string)$r->input('reviewer','system'),(string)$r->input('reason',''),$r->input('conditions',[])));});
});
