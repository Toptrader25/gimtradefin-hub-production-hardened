<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class TrustRiskService
{
    public function assessEntity(string $entityId): array
    {
        $entity = DB::table('entities')->where('id',$entityId)->first();
        if (!$entity) throw new \RuntimeException('Entity not found: '.$entityId);
        $signals = [];
        $this->entitySignals($entity, $signals);
        $this->activitySignals($entityId, $signals);
        $this->contradictionSignals($entityId, $signals);
        $this->persistSignals('entity',$entityId,$signals);
        return $this->persistAssessment('entity',$entityId,$signals);
    }

    public function assessCandidate(string $candidateId): array
    {
        $candidate = DB::table('source_candidates')->where('id',$candidateId)->first();
        if (!$candidate) throw new \RuntimeException('Candidate not found: '.$candidateId);
        $signals=[];
        $attrs = is_string($candidate->attributes ?? null) ? (json_decode($candidate->attributes,true) ?: []) : ($candidate->attributes ?: []);
        $this->candidateSignals($candidate,$attrs,$signals);
        $entityLink = DB::table('entity_links')->where('source_type','source_candidate')->where('source_id',$candidateId)->latest('created_at')->first();
        if ($entityLink) {
            $entitySignals=[]; $entity=DB::table('entities')->where('id',$entityLink->entity_id)->first();
            if ($entity) $this->entitySignals($entity,$entitySignals);
            $signals=array_merge($signals,$entitySignals);
        }
        $this->persistSignals('candidate',$candidateId,$signals);
        $fp=$this->fingerprintCandidate($candidate,$attrs);
        $duplicates=$this->findDuplicates($candidate,$fp);
        if ($duplicates) $this->createDuplicateCluster($candidateId,$duplicates);
        $this->duplicateSignals($candidateId,$duplicates,$signals);
        $this->persistSignals('candidate',$candidateId,$signals);
        return $this->persistAssessment('candidate',$candidateId,$signals);
    }

    public function fingerprintCandidate(object $candidate,array $attrs=[]): array
    {
        $title=$this->norm($candidate->title ?? '');
        $desc=$this->norm($candidate->description ?? ($attrs['description'] ?? ''));
        $company=$this->norm($attrs['company_name'] ?? $attrs['buyer_name'] ?? $attrs['seller_name'] ?? $attrs['organization'] ?? $attrs['company'] ?? '');
        $product=$this->norm($attrs['product'] ?? $attrs['product_name'] ?? $attrs['commodity'] ?? '');
        $country=$this->norm($candidate->country ?? '');
        $type=$this->norm($candidate->lead_type ?? ($attrs['lead_type'] ?? ''));
        $components=['title'=>$title,'company'=>$company,'product'=>$product,'country'=>$country,'lead_type'=>$type,'content'=>$desc];
        $fingerprint=hash('sha256',implode('|',[$company,$product,$country,$type,$this->contentKey($desc)]));
        DB::table('opportunity_fingerprints')->updateOrInsert(['candidate_id'=>$candidate->id],[
            'id'=>Str::uuid()->toString(),'fingerprint'=>$fingerprint,
            'title_fingerprint'=>hash('sha256',$title),'content_fingerprint'=>hash('sha256',$this->contentKey($desc)),
            'entity_fingerprint'=>$company?hash('sha256',$company):null,'product_fingerprint'=>$product?hash('sha256',$product):null,
            'components'=>json_encode($components),'updated_at'=>now(),'created_at'=>now()
        ]);
        return ['fingerprint'=>$fingerprint,'title'=>$title,'company'=>$company,'product'=>$product,'country'=>$country,'lead_type'=>$type,'content'=>$desc,'components'=>$components];
    }

    private function findDuplicates(object $candidate,array $fp): array
    {
        $rows=DB::table('opportunity_fingerprints')->where('candidate_id','<>',$candidate->id)->orderByDesc('updated_at')->limit(1000)->get();
        $matches=[];
        foreach($rows as $row){
            $c=json_decode($row->components ?: '{}',true) ?: [];
            $sameFp=$row->fingerprint===$fp['fingerprint'];
            $title=$this->similarity($fp['title'],(string)($c['title']??''));
            $content=$this->similarity($fp['content'],(string)($c['content']??''));
            $company=$this->similarity($fp['company'],(string)($c['company']??''));
            $product=$this->similarity($fp['product'],(string)($c['product']??''));
            $country=($fp['country']!=='' && $fp['country']===(string)($c['country']??''))?1:0;
            $score=$sameFp?1:(($title*.30)+($company*.30)+($product*.20)+($content*.15)+($country*.05));
            if($score>=.82) $matches[]=['candidate_id'=>$row->candidate_id,'similarity'=>round($score,4),'features'=>compact('title','company','product','content','country'),'exact'=>$sameFp];
        }
        usort($matches,fn($a,$b)=>$b['similarity']<=>$a['similarity']);
        return array_slice($matches,0,20);
    }

    private function candidateSignals(object $c,array $a,array &$s): void
    {
        $text=strtolower(($c->title??'').' '.($c->description??'').' '.json_encode($a));
        $redFlags=[
            'urgency_without_detail'=>['urgent|immediately|asap|today','Requirement uses high urgency language without structured quantity/deadline evidence.',2],
            'off_platform_payment'=>['western union|crypto|cryptocurrency|gift card|prepayment to personal','Potential high-risk payment instruction detected.',5],
            'contact_mismatch'=>['gmail.com|yahoo.com|outlook.com','Free email domain alone is not fraud; flag only for review when paired with other risk signals.',1],
            'too_good_to_be_true'=>['guaranteed profit|100% guaranteed|risk free|no risk','Unusually strong guarantee language.',4],
            'credential_pressure'=>['send passport|send password|send otp|verification code','Requests sensitive credentials or codes.',5],
        ];
        foreach($redFlags as $code=>$r){if(preg_match('/(?:'.$r[0].')/i',$text))$s[]=$this->signal($code,'content_risk',$r[2],.75,$c->source_slug,'text',$c->url,['matched'=>$r[0]]);}
        if (empty($c->url)) $s[]=$this->signal('missing_source_url','provenance',2,.95,$c->source_slug,'candidate',$c->id,[]);
        $this->velocitySignal($c,$s);
    }

    private function entitySignals(object $e,array &$s): void
    {
        if (!$e->website_domain) $s[]=$this->signal('no_business_domain','identity',2,.85,null,'entity',$e->id,[]);
        if (!$e->country_code) $s[]=$this->signal('missing_country','identity',1,.9,null,'entity',$e->id,[]);
        $ids=DB::table('entity_identifiers')->where('entity_id',$e->id)->get();
        $verified=$ids->where('is_verified',1)->count();
        if ($verified===0) $s[]=$this->signal('no_verified_identifier','identity',2,.9,null,'entity',$e->id,[]);
        $domains=$ids->where('identifier_type','domain')->pluck('normalized_value')->unique()->count();
        if ($domains>3) $s[]=$this->signal('many_domains','identity',3,.7,null,'entity',$e->id,['domain_count'=>$domains]);
    }

    private function activitySignals(string $entityId,array &$s): void
    {
        $obs=DB::table('entity_observations')->where('entity_id',$entityId)->get();
        $sources=$obs->pluck('source_slug')->filter()->unique()->count();
        if ($sources>=3) $s[]=$this->signal('cross_source_presence','reputation',-1,.95,null,'entity',$entityId,['source_count'=>$sources]);
        $recent=$obs->filter(fn($o)=>$o->observed_at && strtotime($o->observed_at)>=strtotime('-180 days'))->count();
        if($recent===0) $s[]=$this->signal('stale_entity_activity','freshness',2,.9,null,'entity',$entityId,[]);
    }

    private function contradictionSignals(string $entityId,array &$s): void
    {
        $obs=DB::table('entity_observations')->where('entity_id',$entityId)->get();
        $countries=$obs->pluck('observed_country')->filter()->map(fn($v)=>$this->norm($v))->unique()->values();
        $domains=$obs->pluck('observed_domain')->filter()->map(fn($v)=>$this->norm($v))->unique()->values();
        if($countries->count()>1) $s[]=$this->signal('country_contradiction','consistency',3,.8,null,'entity',$entityId,['countries'=>$countries->all()]);
        if($domains->count()>2) $s[]=$this->signal('domain_variation','consistency',2,.7,null,'entity',$entityId,['domains'=>$domains->all()]);
    }

    private function velocitySignal(object $c,array &$s): void
    {
        if(empty($c->source_slug)) return;
        $count=DB::table('source_candidates')->where('source_slug',$c->source_slug)->where('created_at','>=',now()->subHour())->count();
        if($count>500) $s[]=$this->signal('source_velocity_spike','behavior',3,.8,$c->source_slug,'candidate',$c->id,['last_hour'=>$count]);
    }

    private function duplicateSignals(string $candidateId,array $matches,array &$s): void
    {
        foreach(array_slice($matches,0,5) as $m){
            $sev=$m['similarity']>=.98?4:($m['similarity']>=.90?3:2);
            $s[]=$this->signal($m['exact']?'exact_duplicate':'near_duplicate','duplication',$sev,$m['similarity'],null,'candidate',$candidateId,$m);
        }
    }

    private function persistSignals(string $type,string $id,array $signals): void
    {
        foreach($signals as $x){
            if (($x['severity']??0)<0) continue;
            DB::table('risk_signals')->insert([
                'id'=>Str::uuid()->toString(),'subject_type'=>$type,'subject_id'=>$id,'signal_code'=>$x['code'],'category'=>$x['category'],
                'severity'=>$x['severity'],'confidence'=>$x['confidence'],'status'=>'open','source_slug'=>$x['source_slug'],
                'evidence_type'=>$x['evidence_type'],'evidence_ref'=>$x['evidence_ref'],'details'=>json_encode($x['details']),
                'observed_at'=>now(),'created_at'=>now(),'updated_at'=>now()
            ]);
        }
    }

    private function persistAssessment(string $type,string $id,array $signals): array
    {
        $positive=array_filter($signals,fn($x)=>($x['severity']??0)>0);
        $weighted=0; $denom=0;
        foreach($positive as $x){$w=min(5,max(1,(int)$x['severity']))*max(0,min(1,(float)$x['confidence']));$weighted+=($w*20);$denom+=100;}
        $score=$denom?min(100,(int)round(($weighted/$denom)*100)):0;
        // Multiple independent high-severity signals compound risk, but do not exceed 100.
        $high=count(array_filter($positive,fn($x)=>$x['severity']>=4));
        $score=min(100,$score+min(20,$high*5));
        $band=$score>=75?'critical':($score>=50?'high':($score>=25?'guarded':'low'));
        $decision=$score>=80?'suppress':($score>=55?'restrict':($score>=30?'review':'allow'));
        $dimensions=[]; foreach($signals as $x)$dimensions[$x['category']]=max($dimensions[$x['category']]??0,(int)$x['severity']);
        $top=array_slice(array_map(fn($x)=>['code'=>$x['code'],'severity'=>$x['severity'],'confidence'=>$x['confidence'],'category'=>$x['category']],$positive),0,10);
        DB::table('risk_assessments')->insert(['id'=>Str::uuid()->toString(),'subject_type'=>$type,'subject_id'=>$id,'risk_score'=>$score,'risk_band'=>$band,'decision'=>$decision,'dimensions'=>json_encode($dimensions),'top_signals'=>json_encode($top),'calculated_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        if($decision!=='allow') $this->openReview($type,$id,$band,$decision,$top);
        return compact('score','band','decision','dimensions','top');
    }

    private function openReview(string $type,string $id,string $band,string $decision,array $top): void
    {
        DB::table('risk_review_cases')->updateOrInsert(['subject_type'=>$type,'subject_id'=>$id,'status'=>'open'],['id'=>Str::uuid()->toString(),'case_type'=>'trust_risk','priority'=>$band==='critical'?'critical':($band==='high'?'high':'medium'),'reason'=>'Automated Trust & Risk Engine requires review.','evidence'=>json_encode($top),'updated_at'=>now(),'created_at'=>now()]);
    }

    private function createDuplicateCluster(string $candidateId,array $matches): void
    {
        if(!$matches)return;
        $top=$matches[0];
        $type=$top['exact']?'exact':'near_duplicate';
        $cluster=DB::table('duplicate_clusters')->where('primary_candidate_id',$top['candidate_id'])->where('status','open')->first();
        $clusterId=$cluster? $cluster->id : Str::uuid()->toString();
        if(!$cluster) DB::table('duplicate_clusters')->insert(['id'=>$clusterId,'cluster_type'=>$type,'status'=>'open','primary_candidate_id'=>$top['candidate_id'],'member_count'=>0,'confidence'=>$top['similarity'],'explanation'=>json_encode(['created_by'=>'stage4','top_match'=>$top]),'created_at'=>now(),'updated_at'=>now()]);
        foreach(array_merge([['candidate_id'=>$top['candidate_id'],'similarity'=>$top['similarity'],'features'=>$top['features']]],array_slice($matches,0,10)) as $m){
            DB::table('duplicate_cluster_members')->updateOrInsert(['cluster_id'=>$clusterId,'candidate_id'=>$m['candidate_id']],['id'=>Str::uuid()->toString(),'similarity'=>$m['similarity'],'membership_type'=>'suspected','features'=>json_encode($m['features']),'updated_at'=>now(),'created_at'=>now()]);
        }
        DB::table('duplicate_clusters')->where('id',$clusterId)->update(['member_count'=>DB::table('duplicate_cluster_members')->where('cluster_id',$clusterId)->count(),'updated_at'=>now()]);
    }

    private function signal(string $code,string $category,int $severity,float $confidence,?string $source,?string $etype,?string $eref,array $details): array {return compact('code','category','severity','confidence','source','etype','eref','details') + ['source_slug'=>$source,'evidence_type'=>$etype,'evidence_ref'=>$eref];}
    private function norm(string $v): string { $v=strtolower(trim($v)); $v=preg_replace('/[^\pL\pN]+/u',' ',\Normalizer::normalize($v,\Normalizer::FORM_KD) ?: $v); return trim(preg_replace('/\s+/',' ',$v)); }
    private function contentKey(string $v): string { $v=preg_replace('/https?:\/\/\S+/i',' ',$v); $v=preg_replace('/\b\d{5,}\b/',' ',$v); return $this->norm($v); }
    private function similarity(string $a,string $b): float {if($a===''||$b==='')return 0;if($a===$b)return 1; similar_text($a,$b,$p); $lev=levenshtein($a,$b);$m=max(strlen($a),strlen($b),1);return round(min(1,($p/100)*.65+max(0,1-$lev/$m)*.35),4);}
}
