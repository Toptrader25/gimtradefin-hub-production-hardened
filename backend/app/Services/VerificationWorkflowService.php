<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

final class VerificationWorkflowService
{
    private const CHECKS = [
        'identity' => [
            ['code'=>'identity.company_exists','weight'=>5],
            ['code'=>'identity.legal_name_consistent','weight'=>4],
            ['code'=>'identity.domain_consistent','weight'=>3],
            ['code'=>'identity.identifier_supported','weight'=>5],
        ],
        'commercial' => [
            ['code'=>'commercial.intent_current','weight'=>5],
            ['code'=>'commercial.requirement_specific','weight'=>4],
            ['code'=>'commercial.counterparty_role_supported','weight'=>4],
            ['code'=>'commercial.contact_relevance','weight'=>3],
        ],
        'evidence' => [
            ['code'=>'evidence.primary_source_available','weight'=>5],
            ['code'=>'evidence.provenance_intact','weight'=>5],
            ['code'=>'evidence.independent_corroboration','weight'=>4],
            ['code'=>'evidence.no_material_contradiction','weight'=>4],
        ],
        'risk' => [
            ['code'=>'risk.no_unresolved_critical_flag','weight'=>5],
            ['code'=>'risk.contact_method_safe','weight'=>2],
            ['code'=>'risk.sanctions_screening_completed','weight'=>5],
            ['code'=>'risk.privacy_review_completed','weight'=>2],
        ],
    ];

    public function createCase(array $input): string
    {
        $now=now(); $id=Str::uuid()->toString();
        $priority=$this->priority((int)($input['risk_score']??0),(int)($input['match_score']??0));
        DB::table('verification_cases')->insert([
            'id'=>$id,'entity_id'=>$input['entity_id']??null,'match_id'=>$input['match_id']??null,
            'candidate_id'=>$input['candidate_id']??null,'case_type'=>$input['case_type']??'opportunity',
            'status'=>'queued','priority'=>$priority,'decision'=>'pending','assigned_to'=>$input['assigned_to']??null,
            'due_at'=>$this->dueAt($priority),'context'=>json_encode($input['context']??[]),'summary'=>json_encode([]),
            'created_at'=>$now,'updated_at'=>$now,
        ]);
        foreach(self::CHECKS as $category=>$checks) foreach($checks as $c){
            DB::table('verification_checks')->insert([
                'id'=>Str::uuid()->toString(),'case_id'=>$id,'check_code'=>$c['code'],'category'=>$category,
                'status'=>'pending','weight'=>$c['weight'],'created_at'=>$now,'updated_at'=>$now,
            ]);
        }
        $this->event($id,'case_created','system',null,['priority'=>$priority]);
        return $id;
    }

    public function start(string $caseId,string $reviewer): array
    {
        $case=$this->case($caseId); $now=now();
        DB::table('verification_cases')->where('id',$caseId)->update(['status'=>'in_review','assigned_to'=>$reviewer,'started_at'=>$case->started_at??$now,'updated_at'=>$now]);
        $this->event($caseId,'review_started','human',$reviewer,[]); return $this->snapshot($caseId);
    }

    public function recordCheck(string $caseId,string $code,string $status,?int $score,?string $finding,?array $evidenceRefs,string $reviewer): array
    {
        $allowed=['verified','failed','not_applicable','needs_review']; if(!in_array($status,$allowed,true)) throw new \InvalidArgumentException('Invalid verification status');
        $now=now();
        $check=DB::table('verification_checks')->where(['case_id'=>$caseId,'check_code'=>$code])->first(); if(!$check) throw new \RuntimeException('Verification check not found');
        DB::table('verification_checks')->where('id',$check->id)->update(['status'=>$status,'score'=>$score,'finding'=>$finding,'evidence_refs'=>json_encode($evidenceRefs??[]),'reviewer'=>$reviewer,'verified_at'=>$now,'updated_at'=>$now]);
        $this->recalculate($caseId); $this->event($caseId,'check_recorded','human',$reviewer,['check_code'=>$code,'status'=>$status,'score'=>$score]);
        return $this->snapshot($caseId);
    }

    public function answerQualification(string $caseId,string $questionCode,?string $answer,?int $score,string $reviewer): void
    {
        $now=now(); DB::table('qualification_answers')->updateOrInsert(['case_id'=>$caseId,'question_code'=>$questionCode],['id'=>Str::uuid()->toString(),'answer'=>$answer,'answer_type'=>'text','score'=>$score,'source'=>'reviewer','reviewer'=>$reviewer,'created_at'=>$now]);
        $this->recalculate($caseId); $this->event($caseId,'qualification_recorded','human',$reviewer,['question_code'=>$questionCode,'score'=>$score]);
    }

    public function decide(string $caseId,string $decision,string $reviewer,string $reason='',array $conditions=[]): array
    {
        $allowed=['approve','approve_with_conditions','reject','request_more_evidence','hold']; if(!in_array($decision,$allowed,true)) throw new \InvalidArgumentException('Invalid decision');
        $case=$this->case($caseId); $snapshot=$this->snapshot($caseId);
        if(in_array($decision,['approve','approve_with_conditions'],true) && !$this->canApprove($snapshot)) throw new \RuntimeException('Approval gate not satisfied: critical checks remain unresolved or evidence is insufficient.');
        $now=now();
        DB::table('verification_cases')->where('id',$caseId)->update(['status'=>in_array($decision,['approve','approve_with_conditions','reject'],true)?'completed':'in_review','decision'=>$decision,'completed_at'=>in_array($decision,['approve','approve_with_conditions','reject'],true)?$now:null,'updated_at'=>$now]);
        DB::table('publication_decisions')->insert(['id'=>Str::uuid()->toString(),'case_id'=>$caseId,'decision'=>$decision,'visibility'=>in_array($decision,['approve','approve_with_conditions'],true)?'publishable':'internal','reason_code'=>$this->reasonCode($decision),'reason'=>$reason,'decided_by'=>$reviewer,'conditions'=>json_encode($conditions),'decided_at'=>$now]);
        $this->event($caseId,'decision_made','human',$reviewer,['decision'=>$decision,'reason'=>$reason,'conditions'=>$conditions]); return $this->snapshot($caseId);
    }

    public function snapshot(string $caseId): array
    {
        $c=$this->case($caseId); $checks=DB::table('verification_checks')->where('case_id',$caseId)->orderBy('category')->orderBy('check_code')->get(); $answers=DB::table('qualification_answers')->where('case_id',$caseId)->get();
        return ['case'=>(array)$c,'checks'=>$checks->map(fn($x)=>(array)$x)->all(),'qualification'=>$answers->map(fn($x)=>(array)$x)->all(),'gates'=>$this->gates($checks),'next_actions'=>$this->nextActions($checks,$c)];
    }

    private function recalculate(string $caseId): void
    {
        $checks=DB::table('verification_checks')->where('case_id',$caseId)->get(); $eligible=$checks->whereIn('status',['verified','failed','not_applicable']); $weighted=0;$total=0;
        foreach($eligible as $c){$total+=(int)$c->weight;$weighted+=(int)$c->weight*(int)($c->score??($c->status==='verified'?100:($c->status==='not_applicable'?100:0)))/100;}
        $verification=$total?round(100*$weighted/$total):0;
        $answers=DB::table('qualification_answers')->where('case_id',$caseId)->get(); $qualification=$answers->count()?round($answers->avg(fn($x)=>(int)($x->score??0))):0;
        DB::table('verification_cases')->where('id',$caseId)->update(['verification_score'=>$verification,'qualification_score'=>$qualification,'summary'=>json_encode(['verification_score'=>$verification,'qualification_score'=>$qualification]),'updated_at'=>now()]);
    }
    private function canApprove(array $s): bool { $g=$s['gates']; return $g['critical_unresolved']===0 && $g['required_failed']===0 && $g['evidence_ready']===true && $s['case']['verification_score']>=70; }
    private function gates($checks): array { $critical=['identity.identifier_supported','evidence.primary_source_available','evidence.provenance_intact','risk.no_unresolved_critical_flag','risk.sanctions_screening_completed']; $unresolved=0;$failed=0; foreach($checks as $c){if(in_array($c->check_code,$critical,true)&&!in_array($c->status,['verified','not_applicable'],true))$unresolved++; if($c->status==='failed'&&in_array($c->check_code,$critical,true))$failed++;} $evidenceReady=$checks->where('category','evidence')->every(fn($c)=>in_array($c->status,['verified','not_applicable'],true)); return ['critical_unresolved'=>$unresolved,'required_failed'=>$failed,'evidence_ready'=>$evidenceReady]; }
    private function nextActions($checks,$case): array { $out=[]; foreach($checks as $c) if(in_array($c->status,['pending','needs_review'],true)) $out[]='Complete '.$c->check_code; if($case->verification_score<70)$out[]='Increase verification evidence before approval'; return array_values(array_unique($out)); }
    private function priority(int $risk,int $match): string { if($risk>=75||$match>=90)return'high'; if($risk>=50||$match>=75)return'normal'; return'low'; }
    private function dueAt(string $p): Carbon { return now()->addHours($p==='high'?8:($p==='normal'?24:72)); }
    private function reasonCode(string $d): string { return match($d){'approve'=>'verified_publishable','approve_with_conditions'=>'verified_conditional','reject'=>'verification_failed','request_more_evidence'=>'evidence_insufficient','hold'=>'manual_hold',default=>'other'}; }
    private function case(string $id){$c=DB::table('verification_cases')->where('id',$id)->first();if(!$c)throw new \RuntimeException('Verification case not found');return$c;}
    private function event(string $caseId,string $type,string $actorType,string $actor, array $payload): void { DB::table('verification_events')->insert(['id'=>Str::uuid()->toString(),'case_id'=>$caseId,'event_type'=>$type,'actor_type'=>$actorType,'actor'=>$actor,'payload'=>json_encode($payload),'created_at'=>now()]); }
}
