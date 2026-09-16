<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CommercialEngagementService
{
 private const STAGES=['approved','contacted','engaged','qualified','introduced','meeting','rfq','quoted','negotiation','won','lost','nurture'];
 private const OUTCOMES=['no_response','responded','interested','not_interested','qualified','meeting_booked','rfq_received','quote_requested','quote_submitted','negotiating','won','lost','nurture'];

 public function createOpportunity(array $input): string {
  $id=Str::uuid()->toString(); $now=now();
  DB::table('engagement_opportunities')->insert([
   'id'=>$id,'entity_id'=>$input['entity_id']??null,'match_id'=>$input['match_id']??null,'verification_case_id'=>$input['verification_case_id']??null,
   'title'=>$input['title']??'Commercial Opportunity','stage'=>'approved','status'=>'open','owner'=>$input['owner']??null,
   'priority'=>max(0,min(100,(int)($input['priority']??50))),'commercial_context'=>json_encode($input['commercial_context']??[]),'created_at'=>$now,'updated_at'=>$now
  ]);
  $this->activity($id,'opportunity_created','internal',$input['owner']??'system',null,null,'Opportunity created',null,$input['commercial_context']??[]);
  $this->task($id,'initial_review','Confirm opportunity owner, target contact and outreach readiness',$input['owner']??null,now()->addHours(24));
  return $id;
 }
 public function addContact(string $opportunityId,array $input): string {
  $id=Str::uuid()->toString(); DB::table('engagement_contacts')->insert([
   'id'=>$id,'opportunity_id'=>$opportunityId,'entity_id'=>$input['entity_id']??null,'name'=>$input['name']??null,'role'=>$input['role']??null,
   'email'=>$input['email']??null,'phone'=>$input['phone']??null,'channel'=>$input['channel']??null,'verified'=>(bool)($input['verified']??false),
   'verification_level'=>$input['verification_level']??'unverified','evidence_refs'=>json_encode($input['evidence_refs']??[]),'created_at'=>now(),'updated_at'=>now()
  ]); return $id;
 }
 public function changeStage(string $id,string $stage,string $actor,string $reason=''): array {
  if(!in_array($stage,self::STAGES,true)) throw new \InvalidArgumentException('Invalid engagement stage');
  $o=$this->opportunity($id); DB::table('engagement_opportunities')->where('id',$id)->update(['stage'=>$stage,'updated_at'=>now()]);
  $this->activity($id,'stage_changed','internal',$actor,null,null,$reason?:'Stage changed to '.$stage,null,['from'=>$o->stage,'to'=>$stage]);
  return $this->snapshot($id);
 }
 public function logActivity(string $id,array $input): string {
  return $this->activity($id,$input['type']??'note',$input['direction']??'internal',$input['actor']??'system',$input['channel']??null,$input['subject']??null,$input['body']??null,$input['outcome']??null,$input['metadata']??[]);
 }
 public function createMessage(string $id,array $input): string {
  $channel=$input['channel']??'email'; if(!in_array($channel,['email','whatsapp','linkedin','phone','sms','other'],true)) throw new \InvalidArgumentException('Unsupported channel');
  $mid=Str::uuid()->toString(); DB::table('engagement_messages')->insert(['id'=>$mid,'opportunity_id'=>$id,'contact_id'=>$input['contact_id']??null,'channel'=>$channel,'direction'=>'outbound','status'=>$input['status']??'draft','subject'=>$input['subject']??null,'body'=>$input['body']??'','provider'=>$input['provider']??null,'metadata'=>json_encode($input['metadata']??[]),'created_at'=>now(),'updated_at'=>now()]);
  $this->activity($id,'message_drafted','outbound',$input['actor']??'system',$channel,$input['subject']??null,$input['body']??null,null,['message_id'=>$mid]); return $mid;
 }
 public function markMessageSent(string $messageId,string $actor='system'): void {
  $m=DB::table('engagement_messages')->where('id',$messageId)->first(); if(!$m) throw new \RuntimeException('Message not found');
  DB::table('engagement_messages')->where('id',$messageId)->update(['status'=>'sent','sent_at'=>now(),'updated_at'=>now()]);
  $this->activity($m->opportunity_id,'message_sent','outbound',$actor,$m->channel,$m->subject,$m->body,null,['message_id'=>$messageId]);
 }
 public function recordOutcome(string $id,string $outcome,?int $score,string $notes,string $actor): void {
  if(!in_array($outcome,self::OUTCOMES,true)) throw new \InvalidArgumentException('Invalid outcome');
  DB::table('engagement_outcomes')->insert(['id'=>Str::uuid()->toString(),'opportunity_id'=>$id,'outcome'=>$outcome,'score'=>$score===null?null:max(0,min(100,$score)),'notes'=>$notes,'recorded_by'=>$actor,'occurred_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
  $map=['responded'=>'engaged','interested'=>'engaged','qualified'=>'qualified','meeting_booked'=>'meeting','rfq_received'=>'rfq','quote_requested'=>'rfq','quote_submitted'=>'quoted','negotiating'=>'negotiation','won'=>'won','lost'=>'lost','nurture'=>'nurture'];
  if(isset($map[$outcome])) DB::table('engagement_opportunities')->where('id',$id)->update(['stage'=>$map[$outcome],'status'=>in_array($map[$outcome],['won','lost'],true)?'closed':'open','updated_at'=>now()]);
  $this->activity($id,'outcome_recorded','internal',$actor,null,null,$notes,$outcome,['score'=>$score]);
 }
 public function nextBestActions(string $id): array {
  $o=$this->opportunity($id); $contacts=DB::table('engagement_contacts')->where('opportunity_id',$id)->get(); $open=DB::table('engagement_tasks')->where(['opportunity_id'=>$id,'status'=>'open'])->count();
  $actions=[];
  if($contacts->count()===0) $actions[]='Identify and verify a relevant decision-maker/contact';
  if($contacts->where('verified',true)->count()===0 && $contacts->count()>0) $actions[]='Verify the contact before external outreach';
  if(in_array($o->stage,['approved','nurture'],true)) $actions[]='Prepare personalized first outreach using evidence-backed facts only';
  if($o->stage==='contacted') $actions[]='Monitor for response and schedule follow-up according to the approved cadence';
  if($o->stage==='engaged') $actions[]='Qualify requirement, timing, volume and commercial terms';
  if($o->stage==='qualified') $actions[]='Make a controlled introduction to the best verified counterparty';
  if($o->stage==='meeting') $actions[]='Capture meeting outcome and agreed next step';
  if($o->stage==='rfq') $actions[]='Coordinate RFQ requirements and preserve the buyer\'s original terms';
  if($o->stage==='quoted') $actions[]='Track quote response and commercial objections';
  if($o->stage==='negotiation') $actions[]='Record negotiation milestones and decision blockers';
  if($open===0 && !in_array($o->stage,['won','lost'],true)) $actions[]='Create a dated follow-up task';
  return array_values(array_unique($actions));
 }
 public function snapshot(string $id): array { $o=$this->opportunity($id); return ['opportunity'=>(array)$o,'contacts'=>DB::table('engagement_contacts')->where('opportunity_id',$id)->get()->map(fn($x)=>(array)$x)->all(),'activities'=>DB::table('engagement_activities')->where('opportunity_id',$id)->orderByDesc('occurred_at')->limit(100)->get()->map(fn($x)=>(array)$x)->all(),'tasks'=>DB::table('engagement_tasks')->where('opportunity_id',$id)->orderBy('due_at')->get()->map(fn($x)=>(array)$x)->all(),'messages'=>DB::table('engagement_messages')->where('opportunity_id',$id)->orderByDesc('created_at')->get()->map(fn($x)=>(array)$x)->all(),'outcomes'=>DB::table('engagement_outcomes')->where('opportunity_id',$id)->orderByDesc('occurred_at')->get()->map(fn($x)=>(array)$x)->all(),'next_best_actions'=>$this->nextBestActions($id)]; }
 private function opportunity(string $id){$o=DB::table('engagement_opportunities')->where('id',$id)->first(); if(!$o) throw new \RuntimeException('Opportunity not found'); return $o;}
 private function activity(string $id,string $type,string $direction,string $actor,?string $channel,?string $subject,?string $body,?string $outcome,array $metadata): string { $aid=Str::uuid()->toString(); DB::table('engagement_activities')->insert(['id'=>$aid,'opportunity_id'=>$id,'type'=>$type,'direction'=>$direction,'actor'=>$actor,'channel'=>$channel,'subject'=>$subject,'body'=>$body,'outcome'=>$outcome,'metadata'=>json_encode($metadata),'occurred_at'=>now(),'created_at'=>now(),'updated_at'=>now()]); return $aid; }
 private function task(string $id,string $type,string $title,?string $assignee,$due): string { $tid=Str::uuid()->toString(); DB::table('engagement_tasks')->insert(['id'=>$tid,'opportunity_id'=>$id,'task_type'=>$type,'status'=>'open','assignee'=>$assignee,'title'=>$title,'due_at'=>$due,'created_at'=>now(),'updated_at'=>now()]); return $tid; }
}
