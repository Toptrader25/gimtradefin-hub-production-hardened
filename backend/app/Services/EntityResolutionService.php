<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

final class EntityResolutionService
{
    public function __construct(private EntityNormalizer $normalizer) {}

    public function resolveCandidate(string $candidateId): array
    {
        $candidate = DB::table('source_candidates')->where('id', $candidateId)->first();
        if (!$candidate) throw new \RuntimeException('Candidate not found: '.$candidateId);

        $attrs = is_string($candidate->attributes) ? (json_decode($candidate->attributes, true) ?: []) : ($candidate->attributes ?: []);
        $obs = $this->buildObservation($candidate, $attrs);
        $observationId = Str::uuid()->toString();

        DB::table('entity_observations')->insert([
            'id'=>$observationId,'candidate_id'=>$candidate->id,'manifest_id'=>$this->manifestForCandidate($candidate->id),
            'source_slug'=>$candidate->source_slug,'observed_name'=>$obs['name'],'observed_country'=>$obs['country'],
            'observed_domain'=>$obs['domain'],'observed_url'=>$candidate->url,'observed_address'=>$obs['address'],
            'observed_phone'=>$obs['phone'],'observed_email_domain'=>$obs['email_domain'],
            'observed_attributes'=>json_encode($attrs),'observed_at'=>$candidate->last_seen_at,'created_at'=>now(), 'updated_at'=>now()
        ]);

        $candidates = $this->findCandidates($obs, $candidate->source_slug);
        $results=[];
        foreach ($candidates as $entity) {
            $scored = $this->score($obs, $entity);
            $decision = $this->decision($scored['score'], $scored['features']);
            $id = Str::uuid()->toString();
            DB::table('entity_match_candidates')->insert([
                'id'=>$id,'observation_id'=>$observationId,'entity_id'=>$entity->id,'score'=>$scored['score'],
                'decision'=>$decision,'features'=>json_encode($scored['features']),'reasons'=>json_encode($scored['reasons']),
                'created_at'=>now(),'updated_at'=>now()
            ]);
            $results[]=['entity_id'=>$entity->id,'score'=>$scored['score'],'decision'=>$decision,'features'=>$scored['features'],'reasons'=>$scored['reasons']];
        }

        usort($results, fn($a,$b)=>$b['score']<=>$a['score']);
        $top = $results[0] ?? null;
        $linked = false;
        if ($top && $top['decision'] === 'auto_link') {
            $this->linkObservation($candidate, $observationId, $top);
            $linked = true;
        } elseif (!$top || $top['score'] < 0.55) {
            $entityId = $this->createEntityFromObservation($candidate, $obs, $observationId);
            $linked = true;
            $top = ['entity_id'=>$entityId,'score'=>1.0,'decision'=>'new_entity','features'=>[],'reasons'=>['No sufficiently strong existing entity match; new entity created.']];
        }

        return ['observation_id'=>$observationId,'linked'=>$linked,'top'=>$top,'candidates'=>$results];
    }

    private function buildObservation(object $candidate, array $attrs): array
    {
        $name = $attrs['company_name'] ?? $attrs['buyer_name'] ?? $attrs['seller_name'] ?? $attrs['organization'] ?? $attrs['company'] ?? null;
        $email = $attrs['email'] ?? null;
        return [
            'name'=>$name ?: $candidate->title,
            'country'=>$candidate->country,
            'domain'=>$this->normalizer->domain($attrs['website'] ?? $attrs['domain'] ?? $candidate->url),
            'address'=>$attrs['address'] ?? null,
            'phone'=>$attrs['phone'] ?? $attrs['telephone'] ?? null,
            'email_domain'=>$this->normalizer->emailDomain($email),
            'registration'=>$attrs['registration_number'] ?? $attrs['company_registration'] ?? $attrs['registration_no'] ?? null,
            'tax_id'=>$attrs['tax_id'] ?? null,
            'lei'=>$attrs['lei'] ?? null,
        ];
    }

    private function findCandidates(array $o, string $sourceSlug): array
    {
        $name = $this->normalizer->name($o['name']);
        $domain = $this->normalizer->domain($o['domain']);
        $reg = $this->normalizer->registration($o['registration']);
        $ids = [];
        if ($domain) $ids = array_merge($ids, DB::table('entity_identifiers')->where('identifier_type','domain')->where('normalized_value',$domain)->pluck('entity_id')->all());
        if ($reg) $ids = array_merge($ids, DB::table('entity_identifiers')->whereIn('identifier_type',['registration_no','tax_id','lei'])->where('normalized_value',$reg)->pluck('entity_id')->all());
        if ($name) $ids = array_merge($ids, DB::table('entity_aliases')->where('normalized_alias',$name)->pluck('entity_id')->all());
        $ids = array_values(array_unique($ids));
        $query = DB::table('entities')->where('status','active');
        if ($ids) $query->whereIn('id',$ids);
        else if ($o['country']) $query->where('country_code', $this->countryCode($o['country']))->where('canonical_name','like','%'.str_replace('%','\\%',$name).'%')->limit(50);
        else $query->where('canonical_name','like','%'.str_replace('%','\\%',$name).'%')->limit(50);
        return $query->get()->all();
    }

    private function score(array $o, object $e): array
    {
        $features=[]; $reasons=[];
        $on = $this->normalizer->name($o['name']); $en=$this->normalizer->name($e->canonical_name);
        $name = $this->similarity($on,$en);
        $transName = $this->normalizer->transliteratedName($o['name']);
        $transEntity = $this->normalizer->transliteratedName($e->canonical_name);
        $trans = $this->similarity($transName,$transEntity);
        $features['name_similarity']=$name; $features['transliterated_name_similarity']=$trans;
        if ($name >= .92) $reasons[]='Very strong normalized name match.'; elseif($name>=.78)$reasons[]='Strong name similarity.';

        $domain = $this->identifierScore('domain',$this->normalizer->domain($o['domain']),$e->id);
        $features['domain_match']=$domain;
        $reg = $this->identifierScore('registration_no',$this->normalizer->registration($o['registration']),$e->id);
        $features['registration_match']=$reg;
        $tax = $this->identifierScore('tax_id',$this->normalizer->registration($o['tax_id']),$e->id);
        $features['tax_id_match']=$tax;
        $phone = $this->identifierScore('phone',$this->normalizer->phone($o['phone']),$e->id);
        $features['phone_match']=$phone;
        $email = $this->identifierScore('email_domain',$this->normalizer->domain($o['email_domain']),$e->id);
        $features['email_domain_match']=$email;
        $country = $this->countryScore($o['country'], $e->country_code, $e->country_name); $features['country_match']=$country;
        $address = $this->addressSimilarity($o['address'], $e->id); $features['address_similarity']=$address;

        // Hard identity identifiers dominate. A name alone never auto-merges.
        $score = ($name*.24)+($trans*.08)+($domain*.22)+($reg*.23)+($tax*.09)+($phone*.06)+($email*.03)+($country*.03)+($address*.02);
        if ($reg >= 1 || $tax >= 1) $score = max($score, .97);
        if ($domain >= 1 && max($name,$trans) >= .80) $score = max($score, .94);
        if ($phone >= 1 && $name >= .90) $score = max($score, .92);
        $score = min(1, $score);
        if ($domain >= 1) $reasons[]='Exact normalized domain match.';
        if ($reg >= 1) $reasons[]='Exact registration identifier match.';
        if ($tax >= 1) $reasons[]='Exact tax identifier match.';
        if ($phone >= 1) $reasons[]='Exact normalized phone match.';
        if ($country >= 1) $reasons[]='Country matches.';
        return ['score'=>round($score,4),'features'=>$features,'reasons'=>$reasons];
    }

    private function decision(float $score,array $f): string
    {
        // Auto-link only with strong independent identity evidence.
        $hard = ($f['registration_match']>=1 || $f['tax_id_match']>=1 || ($f['domain_match']>=1 && max($f['name_similarity'],$f['transliterated_name_similarity'])>=.80) || ($f['phone_match']>=1 && max($f['name_similarity'],$f['transliterated_name_similarity'])>=.90));
        if ($score >= .94 && $hard) return 'auto_link';
        if ($score >= .60) return 'review';
        return 'reject';
    }

    private function linkObservation(object $candidate,string $observationId,array $top): void
    {
        DB::table('entity_links')->insert([
            'id'=>Str::uuid()->toString(),'entity_id'=>$top['entity_id'],'source_type'=>'source_candidate','source_id'=>$candidate->id,
            'link_type'=>'resolved','confidence'=>$top['score'],'resolution_method'=>'stage3_entity_resolution',
            'evidence'=>json_encode(['observation_id'=>$observationId,'features'=>$top['features'],'reasons'=>$top['reasons']]),
            'created_at'=>now(),'updated_at'=>now()
        ]);
        DB::table('entity_observations')->where('id',$observationId)->update(['entity_id'=>$top['entity_id'],'updated_at'=>now()]);
        $this->refreshEntity($top['entity_id']);
    }

    private function createEntityFromObservation(object $candidate,array $o,string $observationId): string
    {
        $id=Str::uuid()->toString(); $now=now();
        $countryCode=$this->countryCode($o['country']); $domain=$this->normalizer->domain($o['domain']);
        DB::table('entities')->insert([
            'id'=>$id,'entity_type'=>'organization','canonical_name'=>trim($o['name'] ?: $candidate->title),
            'country_code'=>$countryCode,'country_name'=>$o['country'],'website_domain'=>$domain ?: null,
            'resolution_confidence'=>.60,'resolution_state'=>'new','first_seen_at'=>$candidate->first_seen_at,'last_seen_at'=>$candidate->last_seen_at,
            'created_at'=>$now,'updated_at'=>$now
        ]);
        $this->addAlias($id,$o['name'],$candidate->source_slug);
        $this->addIdentifier($id,'domain',$domain,$candidate->source_slug,false,.70);
        foreach ([['registration_no',$o['registration']],['tax_id',$o['tax_id']],['lei',$o['lei']],['phone',$this->normalizer->phone($o['phone'])],['email_domain',$this->normalizer->domain($o['email_domain'])]] as [$type,$value]) if($value) $this->addIdentifier($id,$type,$value,$candidate->source_slug,false,.70);
        DB::table('entity_observations')->where('id',$observationId)->update(['entity_id'=>$id,'updated_at'=>$now]);
        DB::table('entity_links')->insert(['id'=>Str::uuid()->toString(),'entity_id'=>$id,'source_type'=>'source_candidate','source_id'=>$candidate->id,'link_type'=>'resolved','confidence'=>.60,'resolution_method'=>'new_entity_from_observation','evidence'=>json_encode(['observation_id'=>$observationId]),'created_at'=>$now,'updated_at'=>$now]);
        return $id;
    }

    private function addAlias(string $entityId,?string $alias,?string $source): void { $n=$this->normalizer->name($alias); if(!$n)return; DB::table('entity_aliases')->updateOrInsert(['entity_id'=>$entityId,'normalized_alias'=>$n,'alias_type'=>'name'],['id'=>Str::uuid()->toString(),'alias'=>$alias,'source_slug'=>$source,'updated_at'=>now(),'created_at'=>now()]); }
    private function addIdentifier(string $entityId,string $type,?string $value,?string $source,bool $verified,float $confidence): void { if(!$value)return; DB::table('entity_identifiers')->updateOrInsert(['entity_id'=>$entityId,'identifier_type'=>$type,'normalized_value'=>$value],['id'=>Str::uuid()->toString(),'display_value'=>$value,'source_slug'=>$source,'is_verified'=>$verified,'confidence'=>$confidence,'updated_at'=>now(),'created_at'=>now()]); }
    private function identifierScore(string $type,string $value,string $entityId): float { if(!$value)return 0; return DB::table('entity_identifiers')->where('entity_id',$entityId)->where('identifier_type',$type)->where('normalized_value',$value)->exists()?1:0; }
    private function addressSimilarity(?string $address,string $entityId): float { if(!$address)return 0; $a=$this->normalizer->address($address); $rows=DB::table('entity_observations')->where('entity_id',$entityId)->whereNotNull('observed_address')->pluck('observed_address'); $best=0; foreach($rows as $r){$best=max($best,$this->similarity($a,$this->normalizer->address($r)));} return $best; }
    private function countryScore(?string $country,?string $code,?string $name): float { if(!$country)return 0; $a=strtolower(trim($country)); $b=strtolower(trim((string)$name)); if($a===$b || $a===$this->countryCode($b) || strtolower((string)$code)===$a)return 1; return 0; }
    private function similarity(string $a,string $b): float
    {
        if($a===''||$b==='')return 0;
        if($a===$b)return 1;
        similar_text($a,$b,$p);
        $lev=levenshtein($a,$b);
        $max=max(strlen($a),strlen($b),1);
        $base=(($p/100)*.55)+(max(0,1-$lev/$max)*.25);
        $ta=$this->normalizer->tokens($a); $tb=$this->normalizer->tokens($b);
        $inter=count(array_intersect($ta,$tb)); $union=count(array_unique(array_merge($ta,$tb)));
        $j=$union?($inter/$union):0;
        return round(min(1,$base+($j*.20)),4);
    }
    private function countryCode(?string $country): ?string { if(!$country)return null; $map=['malaysia'=>'MY','china'=>'CN','vietnam'=>'VN','singapore'=>'SG','indonesia'=>'ID','thailand'=>'TH','india'=>'IN','bangladesh'=>'BD','pakistan'=>'PK','philippines'=>'PH','japan'=>'JP','south korea'=>'KR','korea'=>'KR','united arab emirates'=>'AE','uae'=>'AE','saudi arabia'=>'SA','kenya'=>'KE','tanzania'=>'TZ','uganda'=>'UG','rwanda'=>'RW','united kingdom'=>'GB','uk'=>'GB','united states'=>'US','usa'=>'US','germany'=>'DE','france'=>'FR','netherlands'=>'NL','australia'=>'AU']; $x=strtolower(trim($country)); return $map[$x]??(strlen($x)===2?strtoupper($x):null); }
    private function manifestForCandidate(string $candidateId): ?string { $row=DB::table('candidate_evidence')->where('candidate_id',$candidateId)->latest('created_at')->first(); return $row? $row->manifest_id:null; }
    private function refreshEntity(string $entityId): void { $latest=DB::table('entity_observations')->where('entity_id',$entityId)->max('observed_at'); if($latest)DB::table('entities')->where('id',$entityId)->update(['last_seen_at'=>$latest,'updated_at'=>now()]); }
}
