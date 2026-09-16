<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Stage 5: Commercial Intent Engine.
 *
 * Converts evidence-backed observations into explainable commercial-intent
 * events and a time-decayed account/opportunity intent assessment.
 * It never invents an opportunity: every positive signal must point back
 * to a candidate, source evidence, or an approved first-party event.
 */
final class CommercialIntentService
{
    private const HALF_LIFE_DAYS = 30;

    /** @return array<string,mixed> */
    public function assessCandidate(string $candidateId): array
    {
        $candidate = DB::table('source_candidates')->where('id', $candidateId)->first();
        if (!$candidate) throw new \RuntimeException('Candidate not found: '.$candidateId);

        $attrs = is_string($candidate->attributes ?? null)
            ? (json_decode($candidate->attributes, true) ?: [])
            : ($candidate->attributes ?: []);

        $this->extractEventsFromCandidate($candidate, $attrs);
        return $this->calculateAssessment('candidate', $candidateId);
    }

    /** Re-score an entity from all evidence-backed intent events. */
    public function assessEntity(string $entityId): array
    {
        if (!DB::table('entities')->where('id', $entityId)->exists()) {
            throw new \RuntimeException('Entity not found: '.$entityId);
        }
        return $this->calculateAssessment('entity', $entityId);
    }

    /**
     * Creates a single auditable intent event. The caller must supply a
     * source/evidence reference for non-first-party signals.
     */
    public function recordEvent(array $event): string
    {
        $type = (string)($event['intent_type'] ?? '');
        $family = (string)($event['signal_family'] ?? '');
        if (!isset(self::SIGNALS[$family][$type])) {
            throw new \InvalidArgumentException("Unsupported intent signal: {$family}.{$type}");
        }

        $occurredAt = $event['occurred_at'] ?? now();
        $id = Str::uuid()->toString();
        DB::table('intent_events')->insert([
            'id' => $id,
            'subject_type' => $event['subject_type'],
            'subject_id' => $event['subject_id'],
            'intent_type' => $type,
            'signal_family' => $family,
            'polarity' => self::SIGNALS[$family][$type]['polarity'],
            'base_weight' => self::SIGNALS[$family][$type]['weight'],
            'confidence' => max(0, min(1, (float)($event['confidence'] ?? 0.75))),
            'source_slug' => $event['source_slug'] ?? null,
            'evidence_type' => $event['evidence_type'] ?? null,
            'evidence_ref' => $event['evidence_ref'] ?? null,
            'candidate_id' => $event['candidate_id'] ?? null,
            'occurred_at' => $occurredAt,
            'expires_at' => $event['expires_at'] ?? null,
            'details' => json_encode($event['details'] ?? [], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return $id;
    }

    /** @return array<string,array<string,array{weight:int,polarity:int,stage:int,description:string}>> */
    public static function signals(): array { return self::SIGNALS; }

    private function extractEventsFromCandidate(object $candidate, array $attrs): void
    {
        // Re-running a candidate must be idempotent: replace only engine-derived events.
        DB::table('intent_events')->where('subject_type','candidate')->where('subject_id',$candidate->id)->delete();

        $text = $this->plainText(($candidate->title ?? '').' '.($candidate->description ?? '').' '.json_encode($attrs));
        $leadType = strtolower((string)($candidate->signal_type ?? $attrs['lead_type'] ?? ''));
        $published = $candidate->published_at ?: $candidate->first_seen_at;
        $confidenceBase = $this->sourceConfidence((string)$candidate->source_slug);

        // Declared intent: explicit current commercial requirement is strongest.
        $rules = [
            'buying' => [
                ['looking_for_supplier|seeking supplier|buyers? looking|want to buy|purchase requirement|buying requirement|rfq|request for quotation|import requirement|procurement requirement', 'explicit_buy_request', 1, 0.95],
                ['seeking manufacturer|looking for manufacturer|source .* from|need .* supplier', 'supplier_search', 1, 0.88],
            ],
            'selling' => [
                ['ready to supply|available for export|looking for buyers|seeking buyers|export offer|supplier offer|product available', 'explicit_sell_offer', 1, 0.90],
                ['manufacturer|exporter|wholesaler|distributor|trading company', 'commercial_supply_presence', 2, 0.45],
            ],
            'partnership' => [
                ['joint venture|joint-venture|strategic partner|seeking partner|partnership opportunity|distributor wanted|agent wanted|licensing opportunity', 'explicit_partnership_request', 1, 0.92],
            ],
            'investment' => [
                ['investment opportunity|seeking investor|investor wanted|funding requirement|capital partner', 'explicit_investment_request', 1, 0.88],
            ],
        ];

        foreach ($rules as $family => $familyRules) {
            foreach ($familyRules as [$pattern,$type,$stage,$confidence]) {
                if (preg_match('/(?:'.$pattern.')/iu', $text)) {
                    $this->recordEvent([
                        'subject_type'=>'candidate','subject_id'=>$candidate->id,'candidate_id'=>$candidate->id,
                        'intent_type'=>$type,'signal_family'=>$family,'confidence'=>min($confidence,$confidenceBase),
                        'source_slug'=>$candidate->source_slug,'evidence_type'=>'candidate_text','evidence_ref'=>$candidate->id,
                        'occurred_at'=>$published,'details'=>['lead_type'=>$leadType,'stage'=>$stage,'matched_pattern'=>$pattern],
                    ]);
                }
            }
        }

        // Structured commercial fields materially strengthen declared intent.
        $structured = [
            'quantity' => 'specific_quantity',
            'volume' => 'specific_quantity',
            'deadline' => 'defined_buying_deadline',
            'delivery_date' => 'defined_buying_deadline',
            'target_price' => 'target_price_defined',
            'incoterm' => 'trade_terms_defined',
            'moq' => 'specific_quantity',
            'required_quantity' => 'specific_quantity',
        ];
        foreach ($structured as $field=>$type) {
            if (!empty($attrs[$field])) {
                $family = $this->familyForStructured($leadType, $type);
                if (!isset(self::SIGNALS[$family][$type])) { continue; }
                $this->recordEvent([
                    'subject_type'=>'candidate','subject_id'=>$candidate->id,'candidate_id'=>$candidate->id,
                    'intent_type'=>$type,'signal_family'=>$family,'confidence'=>min(.92,$confidenceBase),
                    'source_slug'=>$candidate->source_slug,'evidence_type'=>'candidate_attribute','evidence_ref'=>$candidate->id,
                    'occurred_at'=>$published,'details'=>['field'=>$field],
                ]);
                break;
            }
        }

        // Timing/recency is handled by scoring, not by inventing a separate intent event.
        if ($this->isRecent($published, 14)) {
            $this->recordEvent([
                'subject_type'=>'candidate','subject_id'=>$candidate->id,'candidate_id'=>$candidate->id,
                'intent_type'=>'recent_activity','signal_family'=>$this->familyForLeadType($leadType),
                'confidence'=>min(.95,$confidenceBase),'source_slug'=>$candidate->source_slug,
                'evidence_type'=>'candidate_timestamp','evidence_ref'=>$candidate->id,'occurred_at'=>$published,
                'details'=>['age_days'=>$this->ageDays($published)],
            ]);
        }

        // Negative signal: stale/expired requirement. It reduces intent but does not accuse anyone.
        if ($published && $this->ageDays($published) > 120) {
            $this->recordEvent([
                'subject_type'=>'candidate','subject_id'=>$candidate->id,'candidate_id'=>$candidate->id,
                'intent_type'=>'stale_requirement','signal_family'=>$this->familyForLeadType($leadType),
                'confidence'=>.90,'source_slug'=>$candidate->source_slug,'evidence_type'=>'candidate_timestamp','evidence_ref'=>$candidate->id,
                'occurred_at'=>$published,'details'=>['age_days'=>$this->ageDays($published)],
            ]);
        }
    }

    private function calculateAssessment(string $subjectType, string $subjectId): array
    {
        $events = DB::table('intent_events')->where('subject_type',$subjectType)->where('subject_id',$subjectId)->get();
        $byFamily = [];
        $weighted = 0.0; $positive = 0.0; $negative = 0.0; $independentSources = [];
        $top = [];

        foreach ($events as $event) {
            if ($event->expires_at && strtotime($event->expires_at) < time()) continue;
            $age = max(0, $this->ageDays($event->occurred_at));
            $decay = pow(0.5, $age / self::HALF_LIFE_DAYS);
            $confidence = max(0, min(1,(float)$event->confidence));
            $value = ((float)$event->base_weight) * $confidence * $decay * ((int)$event->polarity);
            $weighted += $value;
            if ($value >= 0) $positive += $value; else $negative += abs($value);
            $family = $event->signal_family;
            $byFamily[$family] = ($byFamily[$family] ?? 0) + $value;
            if ($event->source_slug) $independentSources[$event->source_slug] = true;
            $top[] = ['id'=>$event->id,'type'=>$event->intent_type,'family'=>$family,'value'=>round($value,3),'age_days'=>$age,'confidence'=>(float)$event->confidence];
        }

        // Corroboration is a modest multiplier, deliberately capped to prevent source-count gaming.
        $sourceBonus = min(15, max(0,count($independentSources)-1) * 4);
        $raw = max(0,min(100, 50 + ($weighted * 2.2) + $sourceBonus));
        $score = (int)round($raw);
        $stage = $this->stageForScore($score, $positive, $negative);
        $confidence = $this->assessmentConfidence($events, count($independentSources));
        $temperature = $this->temperature($score, $stage);

        usort($top,fn($a,$b)=>$b['value']<=>$a['value']);
        $assessment = [
            'intent_score'=>$score,
            'intent_stage'=>$stage,
            'buying_temperature'=>$temperature,
            'confidence'=>$confidence,
            'positive_signal_strength'=>round($positive,3),
            'negative_signal_strength'=>round($negative,3),
            'independent_source_count'=>count($independentSources),
            'dimensions'=>array_map(fn($v)=>round($v,3),$byFamily),
            'top_signals'=>array_slice($top,0,10),
            'calculated_at'=>now()->toISOString(),
        ];

        DB::table('intent_assessments')->insert([
            'id'=>Str::uuid()->toString(),'subject_type'=>$subjectType,'subject_id'=>$subjectId,
            'intent_score'=>$score,'intent_stage'=>$stage,'buying_temperature'=>$temperature,
            'confidence'=>$confidence,'dimensions'=>json_encode($assessment['dimensions']),
            'top_signals'=>json_encode($assessment['top_signals']),
            'independent_source_count'=>count($independentSources),'calculated_at'=>now(),'created_at'=>now(),'updated_at'=>now(),
        ]);
        return $assessment;
    }

    private function familyForLeadType(string $leadType): string
    {
        if (str_contains($leadType,'sell') || str_contains($leadType,'supplier') || str_contains($leadType,'offer')) return 'selling';
        if (str_contains($leadType,'partner')) return 'partnership';
        return 'buying';
    }
    private function familyForStructured(string $leadType,string $type): string { return $this->familyForLeadType($leadType); }
    private function sourceConfidence(string $slug): float
    {
        $row=DB::table('source_registry')->where('slug',$slug)->first();
        if(!$row) return .55;
        if(!$row->permission_confirmed) return .45;
        return min(1,max(.55,(float)($row->priority ?? 50)/100));
    }
    private function assessmentConfidence($events,int $sources): float
    {
        if($events->isEmpty()) return 0;
        $avg=$events->avg(fn($e)=>(float)$e->confidence);
        $corroboration=min(1,0.6+($sources*0.1));
        return round(min(1,$avg*$corroboration),4);
    }
    private function stageForScore(int $score,float $positive,float $negative): string
    {
        if($positive<=0 || $score<20) return 'no_signal';
        if($negative > ($positive*0.75)) return 'cooling';
        if($score>=80) return 'active_purchase';
        if($score>=60) return 'evaluation';
        if($score>=40) return 'research';
        return 'early_signal';
    }
    private function temperature(int $score,string $stage): int
    {
        return match($stage){
            'active_purchase'=>5,'evaluation'=>4,'research'=>3,'early_signal'=>2,'cooling'=>1,default=>0
        };
    }
    private function plainText(string $v): string { return trim(preg_replace('/\s+/u',' ',strip_tags($v))); }
    private function ageDays($date): int { return max(0,(int)floor((time()-strtotime((string)$date))/86400)); }
    private function isRecent($date,int $days): bool { return $date && $this->ageDays($date) <= $days; }

    private const SIGNALS = [
        'buying' => [
            'explicit_buy_request'=>['weight'=>30,'polarity'=>1,'stage'=>5,'description'=>'Explicit current request to buy/source a product or service.'],
            'supplier_search'=>['weight'=>22,'polarity'=>1,'stage'=>5,'description'=>'Explicit search for suppliers/manufacturers.'],
            'specific_quantity'=>['weight'=>12,'polarity'=>1,'stage'=>4,'description'=>'Specific volume, MOQ or quantity is stated.'],
            'defined_buying_deadline'=>['weight'=>10,'polarity'=>1,'stage'=>5,'description'=>'Specific purchasing/delivery deadline is stated.'],
            'target_price_defined'=>['weight'=>8,'polarity'=>1,'stage'=>4,'description'=>'Target price/budget information is stated.'],
            'trade_terms_defined'=>['weight'=>5,'polarity'=>1,'stage'=>4,'description'=>'Commercial terms such as Incoterms are stated.'],
            'recent_activity'=>['weight'=>8,'polarity'=>1,'stage'=>3,'description'=>'The commercial signal is recent.'],
            'stale_requirement'=>['weight'=>18,'polarity'=>-1,'stage'=>0,'description'=>'Requirement is materially stale.'],
        ],
        'selling' => [
            'explicit_sell_offer'=>['weight'=>28,'polarity'=>1,'stage'=>5,'description'=>'Explicit current offer to sell/supply.'],
            'commercial_supply_presence'=>['weight'=>8,'polarity'=>1,'stage'=>2,'description'=>'Evidence of commercial supply capability; not itself proof of current selling intent.'],
            'specific_quantity'=>['weight'=>10,'polarity'=>1,'stage'=>4,'description'=>'Specific available quantity/capacity is stated.'],
            'defined_buying_deadline'=>['weight'=>4,'polarity'=>1,'stage'=>3,'description'=>'Time-bound availability signal.'],
            'target_price_defined'=>['weight'=>7,'polarity'=>1,'stage'=>4,'description'=>'Offer pricing is stated.'],
            'trade_terms_defined'=>['weight'=>5,'polarity'=>1,'stage'=>4,'description'=>'Commercial terms are stated.'],
            'recent_activity'=>['weight'=>8,'polarity'=>1,'stage'=>3,'description'=>'The commercial signal is recent.'],
            'stale_requirement'=>['weight'=>15,'polarity'=>-1,'stage'=>0,'description'=>'Offer is materially stale.'],
        ],
        'partnership' => [
            'explicit_partnership_request'=>['weight'=>28,'polarity'=>1,'stage'=>5,'description'=>'Explicit current request for a commercial partner/distributor/agent/JV.'],
            'specific_quantity'=>['weight'=>5,'polarity'=>1,'stage'=>3,'description'=>'Specific scale or territory is stated.'],
            'defined_buying_deadline'=>['weight'=>4,'polarity'=>1,'stage'=>3,'description'=>'Time-bound partnership signal.'],
            'recent_activity'=>['weight'=>8,'polarity'=>1,'stage'=>3,'description'=>'The partnership signal is recent.'],
            'stale_requirement'=>['weight'=>15,'polarity'=>-1,'stage'=>0,'description'=>'Partnership signal is materially stale.'],
        ],
        'investment' => [
            'explicit_investment_request'=>['weight'=>25,'polarity'=>1,'stage'=>5,'description'=>'Explicit request for capital/investor.'],
            'recent_activity'=>['weight'=>7,'polarity'=>1,'stage'=>3,'description'=>'Investment signal is recent.'],
            'stale_requirement'=>['weight'=>15,'polarity'=>-1,'stage'=>0,'description'=>'Investment signal is materially stale.'],
        ],
    ];
}
