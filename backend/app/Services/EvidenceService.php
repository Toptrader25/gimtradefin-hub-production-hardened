<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class EvidenceService
{
    public function createManifest(string $runId, string $payloadId, array $payload): string
    {
        $id = (string) Str::uuid();
        DB::table('evidence_manifests')->insert([
            'id'=>$id,
            'run_id'=>$runId,
            'payload_id'=>$payloadId,
            'source_slug'=>$payload['source_slug'],
            'canonical_url'=>$payload['url'],
            'http_status'=>$payload['http_status'],
            'content_type'=>$payload['content_type'],
            'payload_sha256'=>$payload['sha256'],
            'payload_bytes'=>strlen($payload['body']),
            'captured_at'=>$payload['fetched_at'] ?? now(),
            'retrieval_method'=>'http',
            'policy_status'=>'permitted',
            'headers'=>isset($payload['headers']) ? json_encode($payload['headers']) : null,
            'created_at'=>now(),'updated_at'=>now(),
        ]);

        DB::table('evidence_integrity_checks')->insert([
            'id'=>(string) Str::uuid(),'manifest_id'=>$id,'check_type'=>'payload_sha256',
            'status'=>'passed','details'=>'SHA-256 recorded at ingestion time.','checked_at'=>now(),
            'created_at'=>now(),'updated_at'=>now(),
        ]);
        return $id;
    }

    public function attachCandidateEvidence(string $candidateId, string $manifestId, array $candidate, string $body): array
    {
        $fields = ['title'=>$candidate['title'] ?? null, 'description'=>$candidate['description'] ?? null];
        $created = [];
        foreach ($fields as $field=>$value) {
            if (!is_string($value) || trim($value)==='') continue;
            $match = $this->findExactOrNormalized($body, $value);
            $status = $match ? 'verified' : 'unverified';
            $confidence = $match ? 1.0000 : 0.0000;
            $excerpt = $match['excerpt'] ?? Str::limit($value, 500);
            $evidenceId=(string) Str::uuid();
            DB::table('candidate_evidence')->insert([
                'id'=>$evidenceId,'candidate_id'=>$candidateId,'manifest_id'=>$manifestId,
                'field_name'=>$field,'field_value'=>$value,'locator'=>$match['locator'] ?? null,
                'excerpt'=>$excerpt,'excerpt_sha256'=>hash('sha256',$excerpt),
                'verification_status'=>$status,'verification_method'=>$match ? 'exact_or_normalized_substring' : 'not_found_in_payload',
                'confidence'=>$confidence,'captured_at'=>now(),'created_at'=>now(),'updated_at'=>now(),
            ]);
            $created[]=$evidenceId;
        }
        return $created;
    }

    public function verifyManifest(string $manifestId): array
    {
        $m = DB::table('evidence_manifests')->where('id',$manifestId)->first();
        if (!$m) throw new \RuntimeException('Evidence manifest not found.');
        $p = DB::table('source_payloads')->where('id',$m->payload_id)->first();
        if (!$p) return $this->recordCheck($manifestId,'payload_exists','failed','Payload is missing.');
        $actual = hash('sha256',$p->body);
        $ok = hash_equals($m->payload_sha256,$actual);
        return $this->recordCheck($manifestId,'payload_sha256',$ok?'passed':'failed', $ok?'Payload hash matches manifest.':'Payload hash mismatch detected.');
    }

    private function recordCheck(string $manifestId,string $type,string $status,string $details): array
    {
        DB::table('evidence_integrity_checks')->insert([
            'id'=>(string) Str::uuid(),'manifest_id'=>$manifestId,'check_type'=>$type,
            'status'=>$status,'details'=>$details,'checked_at'=>now(),'created_at'=>now(),'updated_at'=>now(),
        ]);
        return ['manifest_id'=>$manifestId,'check_type'=>$type,'status'=>$status,'details'=>$details];
    }

    private function findExactOrNormalized(string $body, string $needle): ?array
    {
        $needle = trim(preg_replace('/\\s+/u',' ',$needle));
        if ($needle==='') return null;
        $plain = preg_replace('/\\s+/u',' ',strip_tags($body));
        $pos = mb_stripos($plain,$needle,0,'UTF-8');
        if ($pos !== false) return ['locator'=>'text-offset:'.$pos,'excerpt'=>$this->excerpt($plain,$pos,mb_strlen($needle))];
        $short = Str::limit($needle,120,'');
        if ($short!=='' && mb_strlen($short)>25) {
            $pos = mb_stripos($plain,$short,0,'UTF-8');
            if ($pos !== false) return ['locator'=>'normalized-text-offset:'.$pos,'excerpt'=>$this->excerpt($plain,$pos,mb_strlen($short))];
        }
        return null;
    }

    private function excerpt(string $text,int $pos,int $length): string
    {
        $start=max(0,$pos-220); $take=$length+440;
        return Str::limit(mb_substr($text,$start,$take,'UTF-8'),700);
    }
}
