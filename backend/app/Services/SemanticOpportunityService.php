<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Deterministic semantic normalization layer for commercial matching.
 * It does not invent product equivalence. External embeddings/LLMs can be
 * added later behind the same interface once evaluated against real data.
 */
final class SemanticOpportunityService
{
    private array $taxonomy;
    private array $synonyms;
    private array $unitMap;
    private array $currencyMap;

    public function __construct()
    {
        $cfg = config('semantic_matching');
        $this->taxonomy = $cfg['taxonomy'] ?? [];
        $this->synonyms = $cfg['synonyms'] ?? [];
        $this->unitMap = $cfg['units'] ?? [];
        $this->currencyMap = $cfg['currencies'] ?? [];
    }

    public function buildEntityProfile(string $entityId): array
    {
        $facts = DB::table('entity_commercial_facts')->where('entity_id',$entityId)->get();
        $products=[]; $industries=[]; $markets=[]; $terms=[]; $capabilities=[]; $hsCodes=[]; $quantities=[]; $prices=[];
        foreach ($facts as $fact) {
            $type=strtolower((string)$fact->fact_type);
            $value=$this->decodeValue($fact->value ?? null);
            if ($type==='product') $products[]=$this->normalizeProduct($value);
            elseif ($type==='industry') $industries[]=$this->normalizeConcept($value,'industry');
            elseif ($type==='market' || $type==='country') $markets[]=$this->normalizeCountry($value);
            elseif ($type==='commercial_term' || $type==='incoterm' || $type==='payment_term') $terms[]=$this->normalizeTerm($value);
            elseif ($type==='capability') $capabilities[]=$this->normalizeConcept($value,'capability');
            elseif ($type==='hs_code') $hsCodes[]=$this->normalizeHsCode($value);
            elseif ($type==='quantity') { $q=$this->normalizeQuantity($value); if($q) $quantities[]=$q; }
            elseif ($type==='price') { $p=$this->normalizePrice($value); if($p) $prices[]=$p; }
        }
        $profile=[
            'products'=>$this->unique($products), 'industries'=>$this->unique($industries), 'markets'=>$this->unique($markets),
            'terms'=>$this->unique($terms), 'capabilities'=>$this->unique($capabilities), 'hs_codes'=>$this->unique($hsCodes),
            'quantities'=>$quantities, 'prices'=>$prices,
        ];
        DB::table('semantic_entity_profiles')->updateOrInsert(['entity_id'=>$entityId],[
            'id'=>DB::table('semantic_entity_profiles')->where('entity_id',$entityId)->value('id') ?: Str::uuid()->toString(),
            'products'=>json_encode($profile['products']), 'industries'=>json_encode($profile['industries']), 'markets'=>json_encode($profile['markets']),
            'terms'=>json_encode($profile['terms']), 'capabilities'=>json_encode($profile['capabilities']), 'hs_codes'=>json_encode($profile['hs_codes']),
            'quantities'=>json_encode($profile['quantities']), 'prices'=>json_encode($profile['prices']),
            'normalizer_version'=>config('semantic_matching.version','1.0.0'), 'calculated_at'=>now(), 'updated_at'=>now(), 'created_at'=>DB::table('semantic_entity_profiles')->where('entity_id',$entityId)->value('created_at') ?: now(),
        ]);
        return $profile;
    }

    public function compare(array $a,array $b,string $leftRole,string $rightRole): array
    {
        $product=$this->productFit($a['products']??[],$b['products']??[],$a['hs_codes']??[],$b['hs_codes']??[],$leftRole,$rightRole);
        $industry=$this->semanticOverlap($a['industries']??[],$b['industries']??[]);
        $market=$this->marketFit($a['markets']??[],$b['markets']??[],$leftRole,$rightRole);
        $capability=$this->capabilityFit($a['capabilities']??[],$b['capabilities']??[],$leftRole,$rightRole);
        $terms=$this->termFit($a['terms']??[],$b['terms']??[]);
        $quantity=$this->quantityFit($a['quantities']??[],$b['quantities']??[],$leftRole,$rightRole);
        $price=$this->priceFit($a['prices']??[],$b['prices']??[]);
        $route=$this->routeFit($a['markets']??[],$b['markets']??[],$leftRole,$rightRole);
        return compact('product','industry','market','capability','terms','quantity','price','route');
    }

    private function productFit(array $a,array $b,array $ha,array $hb,string $ar,string $br): int
    {
        if ($ha && $hb) {
            foreach($ha as $x) foreach($hb as $y) if($x===$y || (strlen($x)>=4 && substr($x,0,4)===substr($y,0,4))) return 100;
        }
        return $this->semanticOverlap($a,$b);
    }
    private function semanticOverlap(array $a,array $b): int
    {
        if(!$a||!$b) return 25;
        $A=[];$B=[];
        foreach($a as $x) $A=array_merge($A,$this->conceptVariants($x));
        foreach($b as $x) $B=array_merge($B,$this->conceptVariants($x));
        $A=$this->unique($A);$B=$this->unique($B); if(!$A||!$B)return 25;
        $hits=0; foreach($A as $x) foreach($B as $y){ if($x===$y){$hits++;break;} if($this->tokenSimilarity($x,$y)>=0.82){$hits++;break;} }
        return (int)round(100*$hits/max(1,min(count($A),count($B))));
    }
    private function marketFit(array $a,array $b,string $ar,string $br): int
    { if(!$a||!$b)return 35; $over=$this->semanticOverlap($a,$b); return ($ar==='buyer'&&$br==='seller')||($ar==='seller'&&$br==='buyer') ? max($over, $this->regionalFit($a,$b)) : $over; }
    private function regionalFit(array $a,array $b): int
    { $ra=$this->regions($a);$rb=$this->regions($b);if(!$ra||!$rb)return 40;if(array_intersect($ra,$rb))return 85;return count(array_intersect($ra,['asia','east_africa','middle_east','europe','north_america','latin_america'])) && count(array_intersect($rb,['asia','east_africa','middle_east','europe','north_america','latin_america'])) ? 65 : 45; }
    private function routeFit(array $a,array $b,string $ar,string $br): int
    { if(!in_array($ar.':'.$br,['buyer:seller','seller:buyer'],true))return 60; return $this->regionalFit($a,$b); }
    private function capabilityFit(array $a,array $b,string $ar,string $br): int
    { $sellerSide=$br==='seller'?$b:($ar==='seller'?$a:[]); if(!$sellerSide)return $this->semanticOverlap($a,$b); $desired=['manufacturer','exporter','supplier','factory','wholesaler','distributor']; return count(array_intersect($sellerSide,$desired))?90:50; }
    private function termFit(array $a,array $b): int
    { if(!$a||!$b)return 45; foreach($a as $x)foreach($b as $y){$x=strtoupper((string)$x);$y=strtoupper((string)$y);if($x===$y)return 100;if(($x==='FOB'||$x==='CIF')&&($y==='FOB'||$y==='CIF'))return 75;}return 50; }
    private function quantityFit(array $a,array $b,string $ar,string $br): int
    { if(!$a||!$b)return 45; foreach($a as $x)foreach($b as $y){if(($x['unit']??null)!==($y['unit']??null))continue;$q1=(float)($x['value']??0);$q2=(float)($y['value']??0);if(!$q1||!$q2)continue;$ratio=max($q1,$q2)/max(1,min($q1,$q2));if($ratio<=1.25)return 100;if($ratio<=2)return 80;if($ratio<=5)return 60;}return 40; }
    private function priceFit(array $a,array $b): int
    { if(!$a||!$b)return 40;foreach($a as $x)foreach($b as $y){if(($x['currency']??null)!==($y['currency']??null))continue;$lo=(float)($x['min']??$x['value']??0);$hi=(float)($x['max']??$x['value']??0);$lo2=(float)($y['min']??$y['value']??0);$hi2=(float)($y['max']??$y['value']??0);if($hi>0&&$lo2>0&&max($lo,$lo2)<=min($hi,$hi2))return 100;}return 45; }
    private function normalizeProduct($v): string { return $this->normalizeConcept($v,'product'); }
    private function normalizeConcept($v,string $kind): string { $s=is_array($v)?($v['name']??$v['value']??$v['label']??''):(string)$v;$s=Str::lower(trim($s));$s=preg_replace('/[^\pL\pN\s\-\/]+/u',' ',$s);$s=preg_replace('/\s+/u',' ',$s);$s=$this->synonyms[$s]??$s; foreach(($this->taxonomy[$kind]??[]) as $canonical=>$terms){foreach($terms as $term){if($s===$term||str_contains($s,$term))return $canonical;}}return $s; }
    private function normalizeCountry($v): string { $s=Str::lower(trim(is_array($v)?($v['country']??$v['value']??''):(string)$v));$map=['cn'=>'china','china'=>'china','viet nam'=>'vietnam','vn'=>'vietnam','myanmar'=>'myanmar','burma'=>'myanmar','uae'=>'united arab emirates','emirates'=>'united arab emirates'];return $map[$s]??$s; }
    private function normalizeTerm($v): string { $s=Str::upper(trim(is_array($v)?($v['term']??$v['value']??''):(string)$v));return $s; }
    private function normalizeHsCode($v): string { $s=preg_replace('/\D/','',is_array($v)?($v['code']??$v['value']??''):(string)$v);return strlen($s)>=4?substr($s,0,6):$s; }
    private function normalizeQuantity($v): ?array { if(!is_array($v))return null;$value=(float)($v['value']??0);$unit=Str::lower((string)($v['unit']??''));if(!$value||!$unit)return null;$unit=$this->unitMap[$unit]??$unit;return ['value'=>$value,'unit'=>$unit]; }
    private function normalizePrice($v): ?array { if(!is_array($v))return null;$currency=Str::upper((string)($v['currency']??''));$currency=$this->currencyMap[$currency]??$currency;$value=isset($v['value'])?(float)$v['value']:null;$min=isset($v['min'])?(float)$v['min']:$value;$max=isset($v['max'])?(float)$v['max']:$value;if(!$currency||$min===null)return null;return ['currency'=>$currency,'value'=>$value,'min'=>$min,'max'=>$max]; }
    private function regions(array $items): array { $out=[];$map=['china'=>'asia','vietnam'=>'asia','malaysia'=>'asia','indonesia'=>'asia','thailand'=>'asia','japan'=>'asia','south korea'=>'asia','india'=>'asia','bangladesh'=>'asia','pakistan'=>'asia','kenya'=>'east_africa','tanzania'=>'east_africa','uganda'=>'east_africa','rwanda'=>'east_africa','ethiopia'=>'east_africa','uae'=>'middle_east','united arab emirates'=>'middle_east','saudi arabia'=>'middle_east','germany'=>'europe','france'=>'europe','uk'=>'europe','united kingdom'=>'europe','usa'=>'north_america','united states'=>'north_america','canada'=>'north_america','mexico'=>'latin_america'];foreach($items as $x){$s=Str::lower((string)$x);if(isset($map[$s]))$out[]=$map[$s];}return array_values(array_unique($out)); }
    private function conceptVariants($x): array { $x=Str::lower((string)$x);$out=[$x];foreach($this->taxonomy as $terms){foreach($terms as $canonical=>$vals){if($canonical===$x||in_array($x,$vals,true)){$out[]=$canonical;$out=array_merge($out,$vals);}}}return $this->unique($out); }
    private function tokenSimilarity(string $a,string $b): float { if($a===$b)return 1.0;$A=array_values(array_filter(explode(' ',preg_replace('/[^\pL\pN]+/u',' ',$a))));$B=array_values(array_filter(explode(' ',preg_replace('/[^\pL\pN]+/u',' ',$b))));if(!$A||!$B)return 0.0;$i=count(array_intersect($A,$B));return (2*$i)/(count($A)+count($B)); }
    private function unique(array $items): array { $out=[];$seen=[];foreach($items as $x){if(is_array($x))$key=json_encode($x);else $key=Str::lower(trim((string)$x));if($key===''||isset($seen[$key]))continue;$seen[$key]=1;$out[]=$x;}return $out; }
    private function decodeValue($v){if(is_array($v))return $v;$x=json_decode((string)$v,true);return is_array($x)?$x:(string)$v;}
}
