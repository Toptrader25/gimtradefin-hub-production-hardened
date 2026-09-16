<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

final class OpportunityMatchingService
{
    public function __construct(private readonly SemanticOpportunityService $semantic) {}
    private const ROLE_PAIRS = [
        'buyer:seller'=>'trade', 'seller:buyer'=>'trade',
        'capital_seeker:investor'=>'investment', 'investor:capital_seeker'=>'investment',
        'buyer:partner'=>'partnership', 'partner:buyer'=>'partnership',
        'seller:partner'=>'partnership', 'partner:seller'=>'partnership',
        'capital_seeker:partner'=>'partnership', 'partner:capital_seeker'=>'partnership',
    ];

    public function matchEntity(string $entityId, int $limit = 25): array
    {
        $runId = Str::uuid()->toString();
        $started = now();
        DB::table('match_runs')->insert(['id'=>$runId,'run_type'=>'entity_match','status'=>'running','parameters'=>json_encode(['entity_id'=>$entityId,'limit'=>$limit]),'started_at'=>$started,'created_at'=>$started,'updated_at'=>$started]);
        $target = $this->profile($entityId);
        if (!$target) throw new \RuntimeException('Commercial profile not found: '.$entityId);
        $roles = $this->roles($target);
        $candidateIds = DB::table('entity_commercial_profiles')->where('entity_id','<>',$entityId)->pluck('entity_id');
        $results=[]; $evaluated=0;
        foreach ($candidateIds as $otherId) {
            $other=$this->profile($otherId); if(!$other) continue;
            foreach ($roles as $leftRole=>$leftScore) {
                foreach ($this->roles($other) as $rightRole=>$rightScore) {
                    $type=self::ROLE_PAIRS[$leftRole.':'.$rightRole] ?? null; if(!$type) continue;
                    $evaluated++; $calc=$this->score($target,$other,$leftRole,$rightRole,$type);
                    if($calc['score'] < 55) continue;
                    $id=$this->upsertMatch($runId,$entityId,$otherId,$leftRole,$rightRole,$type,$calc);
                    $calc['id']=$id; $calc['left_entity_id']=$entityId; $calc['right_entity_id']=$otherId; $results[]=$calc;
                }
            }
        }
        usort($results,fn($a,$b)=>($b['score']<=>$a['score']) ?: ($b['confidence']<=>$a['confidence']));
        $results=array_slice($results,0,$limit);
        DB::table('match_runs')->where('id',$runId)->update(['status'=>'completed','pairs_evaluated'=>$evaluated,'matches_created'=>count($results),'finished_at'=>now(),'updated_at'=>now()]);
        return ['run_id'=>$runId,'evaluated'=>$evaluated,'matches'=>array_values($results)];
    }

    public function explain(string $matchId): array
    {
        $m=DB::table('opportunity_matches')->where('id',$matchId)->first(); if(!$m) throw new \RuntimeException('Match not found');
        return ['match_id'=>$matchId,'score'=>$m->match_score,'confidence'=>$m->confidence,'recommendation'=>$m->recommendation,'dimensions'=>$this->json($m->dimensions),'evidence'=>$this->json($m->evidence),'gaps'=>$this->json($m->gaps),'risks'=>$this->json($m->risks),'explanation'=>$this->json($m->explanation)];
    }

    private function score($a,$b,string $ar,string $br,string $type): array
    {
        $pa=$this->json($a->products); $pb=$this->json($b->products); $ia=$this->json($a->industries); $ib=$this->json($b->industries); $ma=$this->json($a->markets); $mb=$this->json($b->markets); $ca=$this->json($a->capabilities); $cb=$this->json($b->capabilities); try {
            $sa=DB::table('semantic_entity_profiles')->where('entity_id',$a->entity_id)->first();
            if(!$sa) $sa=(object)$this->semantic->buildEntityProfile($a->entity_id);
            $sb=DB::table('semantic_entity_profiles')->where('entity_id',$b->entity_id)->first();
            if(!$sb) $sb=(object)$this->semantic->buildEntityProfile($b->entity_id);
            $semA=$this->semanticProfileArray($sa); $semB=$this->semanticProfileArray($sb);
            $semantic=$this->semantic->compare($semA,$semB,$ar,$br);
        } catch (\Throwable $e) {
            $semantic=[];
        }
        $product=$semantic['product'] ?? $this->overlap($pa,$pb); $industry=$semantic['industry'] ?? $this->overlap($ia,$ib); $market=$semantic['market'] ?? $this->marketFit($ma,$mb,$ar,$br); $capability=$semantic['capability'] ?? $this->capabilityFit($ca,$cb,$ar,$br); $terms=$semantic['terms'] ?? $this->overlap($ta,$tb);
        $quantity=$semantic['quantity'] ?? 45; $price=$semantic['price'] ?? 45; $route=$semantic['route'] ?? 60;
        $intentA=$this->intent($a->entity_id); $intentB=$this->intent($b->entity_id); $profile=min(100,(int)($a->profile_confidence+$b->profile_confidence)/2);
        $weights = $type==='investment' ? ['product'=>8,'industry'=>12,'market'=>12,'capability'=>8,'terms'=>5,'quantity'=>5,'price'=>5,'route'=>5,'intent'=>25,'profile'=>15] : ['product'=>22,'industry'=>12,'market'=>12,'capability'=>12,'terms'=>7,'quantity'=>7,'price'=>5,'route'=>5,'intent'=>13,'profile'=>5];
        $intentFit=min(100,($intentA+$intentB)/2);
        $dims=['product'=>$product,'industry'=>$industry,'market'=>$market,'capability'=>$capability,'terms'=>$terms,'quantity'=>$quantity,'price'=>$price,'route'=>$route,'intent'=>$intentFit,'profile'=>$profile];
        $score=0; foreach($weights as $k=>$w) $score += $dims[$k]*$w/100; $score=(int)round(min(100,max(0,$score)));
        $evidence=[]; $gaps=[]; $risks=[];
        foreach(['product','industry','market','capability','terms','quantity','price','route'] as $k){ if($dims[$k]>=70) $evidence[]=$k.' compatibility is strong'; elseif($dims[$k]<35) $gaps[]='Weak '.$k.' compatibility'; }
        if($intentFit>=70) $evidence[]='Both sides show meaningful commercial intent'; elseif($intentFit<35) $gaps[]='Commercial intent is weak or stale';
        if($profile<50) $risks[]='Commercial profiles have limited evidence';
        $confidence=(int)round(min(100,($profile*0.45)+($intentFit*0.25)+($this->sourceCount($a->entity_id,$b->entity_id)*5)));
        $recommendation=$score>=85&&$confidence>=70?'strong_match':($score>=70&&$confidence>=55?'review':'weak');
        if($semantic) $this->persistSemanticFeatures($a->entity_id,$b->entity_id,$type,$semantic);
        return ['score'=>$score,'confidence'=>$confidence,'recommendation'=>$recommendation,'dimensions'=>$dims,'semantic'=>$semantic,'evidence'=>$evidence,'gaps'=>$gaps,'risks'=>$risks,'explanation'=>$this->explainText($type,$ar,$br,$dims,$evidence,$gaps)];
    }

    private function semanticProfileArray($p): array { return ['products'=>$this->json($p->products ?? []),'industries'=>$this->json($p->industries ?? []),'markets'=>$this->json($p->markets ?? []),'terms'=>$this->json($p->terms ?? []),'capabilities'=>$this->json($p->capabilities ?? []),'hs_codes'=>$this->json($p->hs_codes ?? []),'quantities'=>$this->json($p->quantities ?? []),'prices'=>$this->json($p->prices ?? [])]; }
    private function persistSemanticFeatures($a,$b,$type,array $semantic): void { $match=DB::table('opportunity_matches')->where(['left_entity_id'=>$a,'right_entity_id'=>$b,'match_type'=>$type])->first(); if(!$match)return; $existing=DB::table('semantic_match_features')->where('match_id',$match->id)->first(); $data=['id'=>$existing->id??Str::uuid()->toString(),'match_id'=>$match->id,'product_score'=>$semantic['product'],'industry_score'=>$semantic['industry'],'market_score'=>$semantic['market'],'capability_score'=>$semantic['capability'],'terms_score'=>$semantic['terms'],'quantity_score'=>$semantic['quantity'],'price_score'=>$semantic['price'],'route_score'=>$semantic['route'],'normalized_evidence'=>json_encode($semantic),'semantic_provider'=>config('semantic_matching.provider','deterministic'),'semantic_version'=>config('semantic_matching.version','1.0.0'),'calculated_at'=>now(),'updated_at'=>now(),'created_at'=>$existing->created_at??now()]; DB::table('semantic_match_features')->updateOrInsert(['match_id'=>$match->id],$data); }
    private function explainText($type,$ar,$br,$dims,$evidence,$gaps): string { $lead=$type==='trade'?'trade opportunity':($type==='investment'?'investment opportunity':'partnership opportunity'); return ucfirst($lead).' between '.$ar.' and '.$br.'. Strongest dimensions: '.implode(', ',array_keys(array_filter($dims,fn($v)=>$v>=70))).'. '.($gaps?'Review: '.implode('; ',$gaps).'.':'No major compatibility gap detected by the current rule set.'); }
    private function profile($id){ return DB::table('entity_commercial_profiles')->where('entity_id',$id)->first(); }
    private function roles($p): array { $r=$this->json($p->roles); return array_filter($r,fn($v)=>$v>=0.25); }
    private function intent($id): int { $v=DB::table('intent_assessments')->where('subject_type','entity')->where('subject_id',$id)->orderByDesc('calculated_at')->value('intent_score'); return (int)($v??0); }
    private function sourceCount($a,$b): int { $x=DB::table('entity_commercial_facts')->whereIn('entity_id',[$a,$b])->whereNotNull('source_slug')->distinct('source_slug')->count('source_slug'); return min(10,$x); }
    private function overlap(array $a,array $b): int { $A=$this->values($a);$B=$this->values($b);if(!$A||!$B)return 20;$common=count(array_intersect($A,$B));return (int)round(100*$common/max(1,min(count($A),count($B)))); }
    private function marketFit(array $a,array $b,string $ar,string $br): int { if($ar==='buyer'&&$br==='seller') return $this->marketDirectional($a,$b); if($ar==='seller'&&$br==='buyer') return $this->marketDirectional($b,$a); return $this->overlap($a,$b); }
    private function marketDirectional(array $buyer,array $seller): int { $A=$this->values($buyer);$B=$this->values($seller); if(!$A||!$B)return 40; return count(array_intersect($A,$B))?90:50; }
    private function capabilityFit(array $a,array $b,string $ar,string $br): int { $A=$this->values($a);$B=$this->values($b); if($ar==='buyer'&&$br==='seller') return count(array_intersect($B,['manufacturer','exporter','supplier','wholesaler','factory','distributor']))?85:40; if($ar==='seller'&&$br==='buyer') return count(array_intersect($A,['manufacturer','exporter','supplier','wholesaler','factory','distributor']))?85:40; return $this->overlap($a,$b); }
    private function values(array $items): array { $out=[];foreach($items as $x){$v=is_array($x)?($x['value']??null):$x;if($v)$out[]=strtolower(trim((string)$v));}return array_values(array_unique($out)); }
    private function upsertMatch($run,$a,$b,$ar,$br,$type,$c): string { $existing=DB::table('opportunity_matches')->where(['left_entity_id'=>$a,'right_entity_id'=>$b,'match_type'=>$type])->first();$id=$existing->id??Str::uuid()->toString();DB::table('opportunity_matches')->updateOrInsert(['left_entity_id'=>$a,'right_entity_id'=>$b,'match_type'=>$type],['id'=>$id,'run_id'=>$run,'left_role'=>$ar,'right_role'=>$br,'match_score'=>$c['score'],'confidence'=>$c['confidence'],'recommendation'=>$c['recommendation'],'status'=>'candidate','dimensions'=>json_encode($c['dimensions']),'evidence'=>json_encode($c['evidence']),'gaps'=>json_encode($c['gaps']),'risks'=>json_encode($c['risks']),'explanation'=>json_encode($c['explanation']),'calculated_at'=>now(),'updated_at'=>now(),'created_at'=>$existing->created_at??now()]);return $id; }
    private function json($v): array { if(is_array($v))return $v;if(!$v)return []; $x=json_decode($v,true);return is_array($x)?$x:[]; }
}
