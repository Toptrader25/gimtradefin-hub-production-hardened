<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

final class CommercialIntelligenceService
{
    public function profileEntity(string $entityId): array
    {
        $entity = DB::table('entities')->where('id',$entityId)->first();
        if (!$entity) throw new \RuntimeException('Entity not found: '.$entityId);

        $observations = DB::table('entity_observations')->where('entity_id',$entityId)->orderByDesc('observed_at')->limit(500)->get();
        $candidateIds = $observations->pluck('candidate_id')->filter()->unique()->values();
        $candidates = $candidateIds->isEmpty() ? collect() : DB::table('source_candidates')->whereIn('id',$candidateIds)->get()->keyBy('id');
        $facts = [];
        $roles = ['buyer'=>0.0,'seller'=>0.0,'partner'=>0.0,'investor'=>0.0,'capital_seeker'=>0.0];
        $industries=[]; $products=[]; $markets=[]; $capabilities=[]; $terms=[]; $investment=[]; $counterparty=[];

        foreach ($observations as $obs) {
            $attrs = is_string($obs->observed_attributes) ? (json_decode($obs->observed_attributes,true) ?: []) : ($obs->observed_attributes ?: []);
            $candidate = $obs->candidate_id ? ($candidates[$obs->candidate_id] ?? null) : null;
            $text = $this->text(($obs->observed_name ?? '').' '.($candidate->title ?? '').' '.($candidate->description ?? '').' '.json_encode($attrs));
            $leadType = strtolower((string)($candidate->signal_type ?? $attrs['lead_type'] ?? ''));
            $source = $obs->source_slug ?: ($candidate->source_slug ?? null);
            $date = $candidate->published_at ?? $obs->observed_at;
            $confidence = $this->sourceConfidence($source);

            $this->scoreRole($roles,$text,$leadType,$confidence);
            $this->extractLists($text,$attrs,$industries,$products,$markets,$capabilities,$terms,$investment);
            $this->extractFacts($entityId,$candidate,$obs,$attrs,$text,$confidence,$facts);
        }

        $roles = array_map(fn($v)=>round(min(1,$v),4),$roles);
        arsort($roles); $primary = array_key_first($roles);
        $profile = [
            'primary_role'=>$primary,
            'roles'=>$roles,
            'industries'=>$this->rank($industries), 'products'=>$this->rank($products), 'markets'=>$this->rank($markets),
            'capabilities'=>$this->rank($capabilities), 'commercial_terms'=>$this->rank($terms),
            'investment_profile'=>$this->rank($investment), 'counterparty_summary'=>$counterparty,
        ];
        $confidence = $this->profileConfidence($observations,$facts,$roles);

        DB::table('entity_commercial_profiles')->updateOrInsert(['entity_id'=>$entityId],[
            'id'=>DB::table('entity_commercial_profiles')->where('entity_id',$entityId)->value('id') ?: Str::uuid()->toString(),
            'primary_role'=>$primary,'roles'=>json_encode($roles),'industries'=>json_encode($profile['industries']),
            'products'=>json_encode($profile['products']),'markets'=>json_encode($profile['markets']),
            'capabilities'=>json_encode($profile['capabilities']),'commercial_terms'=>json_encode($profile['commercial_terms']),
            'investment_profile'=>json_encode($profile['investment_profile']),'counterparty_summary'=>json_encode($counterparty),
            'profile_confidence'=>$confidence,'profiled_at'=>now(),'updated_at'=>now(),'created_at'=>now(),
        ]);
        DB::table('entity_profile_snapshots')->insert(['id'=>Str::uuid()->toString(),'entity_id'=>$entityId,'profile_version'=>'1.0','profile'=>json_encode($profile),'confidence'=>$confidence,'created_at'=>now()]);
        foreach($roles as $role=>$score){
            DB::table('entity_commercial_roles')->insert(['id'=>Str::uuid()->toString(),'entity_id'=>$entityId,'role'=>$role,'score'=>$score,'basis'=>'stage6_rule_engine','signals'=>json_encode(['observation_count'=>$observations->count()]),'calculated_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        }
        return ['entity_id'=>$entityId,'profile_confidence'=>$confidence,'profile'=>$profile,'facts_created'=>count($facts)];
    }

    private function scoreRole(array &$roles,string $text,string $leadType,float $confidence): void {
        $rules=[
            'buyer'=>['looking for supplier','seeking supplier','buyer','rfq','request for quotation','import requirement','purchase requirement','need to buy','sourcing'],
            'seller'=>['manufacturer','exporter','supplier','wholesaler','ready to supply','looking for buyers','product available','export offer'],
            'partner'=>['joint venture','strategic partner','distributor wanted','agent wanted','partnership','licensing','representative wanted'],
            'investor'=>['investor','investment fund','seeking investment','capital provider','private equity','venture capital','family office'],
            'capital_seeker'=>['seeking funding','funding requirement','capital required','raise capital','looking for investment','finance required'],
        ];
        foreach($rules as $role=>$phrases){ foreach($phrases as $p){ if(str_contains($text,$p)){ $roles[$role]+=0.18*$confidence; } } }
        if(str_contains($leadType,'buy')) $roles['buyer']+=.25;
        if(str_contains($leadType,'sell') || str_contains($leadType,'supplier')) $roles['seller']+=.25;
        if(str_contains($leadType,'partner')) $roles['partner']+=.25;
        if(str_contains($leadType,'invest')) $roles['investor']+=.25;
    }

    private function extractLists(string $text,array $attrs,array &$industries,array &$products,array &$markets,array &$capabilities,array &$terms,array &$investment): void {
        $maps=[
            'industries'=>['manufacturing','agriculture','food','textiles','chemicals','electronics','automotive','construction','logistics','pharmaceutical','energy','technology','finance','retail','mining','metals','plastics'],
            'products'=>['rice','oil','steel','cement','fertilizer','machinery','electronics','textiles','garments','coffee','cocoa','seafood','chemicals','plastic','solar','medical devices','pharmaceuticals'],
            'markets'=>['china','vietnam','malaysia','indonesia','thailand','india','bangladesh','pakistan','singapore','japan','korea','uae','saudi arabia','kenya','tanzania','uganda','ethiopia','europe','united states','usa'],
            'capabilities'=>['manufacturer','exporter','importer','distributor','wholesaler','private label','contract manufacturing','factory','warehouse','procurement','sourcing','logistics'],
            'terms'=>['fob','cif','cnf','ddp','dap','exw','lc','letter of credit','open account','moq','bulk'],
            'investment'=>['seed','series a','series b','private equity','venture capital','family office','project finance','working capital','growth capital','acquisition','joint venture','funding'],
        ];
        foreach($maps as $bucket=>$words){ foreach($words as $w){ if(str_contains($text,$w)){ ${$bucket}[$w]=(${$bucket}[$w]??0)+1; } } }
        foreach(['industry'=>'industries','product'=>'products','market'=>'markets','country'=>'markets','capability'=>'capabilities'] as $key=>$bucket){ if(!empty($attrs[$key])){ $v=strtolower(trim((string)$attrs[$key])); if($v) ${$bucket}[$v]=(${$bucket}[$v]??0)+2; } }
    }

    private function extractFacts(string $entityId,$candidate,$obs,array $attrs,string $text,float $confidence,array &$facts): void {
        $fields=['quantity','volume','required_quantity','moq','target_price','currency','incoterm','deadline','delivery_date','capacity','funding_amount','investment_amount','funding_stage'];
        foreach($fields as $field){ if(!empty($attrs[$field])){ $id=Str::uuid()->toString(); DB::table('entity_commercial_facts')->insert(['id'=>$id,'entity_id'=>$entityId,'fact_type'=>$this->factType($field),'fact_key'=>$field,'fact_value'=>(string)$attrs[$field],'normalized_value'=>$this->normalizeValue($attrs[$field]),'unit'=>is_array($attrs[$field])?null:($attrs['unit']??null),'confidence'=>$confidence,'source_slug'=>$obs->source_slug,'candidate_id'=>$obs->candidate_id,'evidence_id'=>null,'observed_at'=>$obs->observed_at,'expires_at'=>null,'metadata'=>json_encode([]),'created_at'=>now(),'updated_at'=>now()]); $facts[]=$field; } }
    }

    private function factType(string $field): string { if(in_array($field,['funding_amount','investment_amount','funding_stage'])) return 'investment'; if(in_array($field,['quantity','volume','required_quantity','moq','capacity'])) return 'commercial_volume'; if(in_array($field,['target_price','currency','incoterm'])) return 'trade_terms'; return 'timing'; }
    private function normalizeValue($v): string { return is_scalar($v)?strtolower(trim((string)$v)):json_encode($v); }
    private function rank(array $values): array { arsort($values); return array_slice(array_map(fn($k,$v)=>['value'=>$k,'signal_count'=>$v],array_keys($values),$values),0,25); }
    private function profileConfidence($observations,array $facts,array $roles): int { $obs=min(40,$observations->count()*4); $fact=min(25,count($facts)*5); $role=max($roles)*35; $source=min(20,count($observations->pluck('source_slug')->filter()->unique())*5); return (int)round(min(100,$obs+$fact+$role+$source)); }
    private function sourceConfidence(?string $slug): float { if(!$slug) return .5; $r=DB::table('source_registry')->where('slug',$slug)->first(); if(!$r) return .5; return $r->permission_confirmed ? min(1,max(.55,((int)$r->priority)/100)) : .45; }
    private function text(string $s): string { return strtolower(trim(preg_replace('/\s+/u',' ',strip_tags($s)))); }
}
