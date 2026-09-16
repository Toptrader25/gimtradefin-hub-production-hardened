<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;

final class EvidenceQualityService
{
    public function scoreCandidate(string $candidateId): array
    {
        $candidate=DB::table('source_candidates')->where('id',$candidateId)->first();
        if(!$candidate) throw new \RuntimeException('Candidate not found.');

        $rows=DB::table('candidate_evidence')->where('candidate_id',$candidateId)->get();
        $manifestIds=$rows->pluck('manifest_id')->unique();
        $manifests=DB::table('evidence_manifests')->whereIn('id',$manifestIds)->get()->keyBy('id');

        $fieldCount=$rows->count();
        $verified=$rows->where('verification_status','verified')->count();
        $coverage=$fieldCount ? $verified/$fieldCount : 0;
        $integrity=0;
        foreach($manifests as $m){
            $check=DB::table('evidence_integrity_checks')->where('manifest_id',$m->id)->where('check_type','payload_sha256')->latest('checked_at')->first();
            if($check && $check->status==='passed') $integrity=1; 
        }
        $source=DB::table('source_registry')->where('slug',$candidate->source_slug)->first();
        $permission=($source && $source->permission_confirmed) ? 1 : 0;
        $freshness=$this->freshness($candidate->last_seen_at);

        $score=(int)round(($coverage*45)+($integrity*30)+($permission*15)+($freshness*10));
        $grade=$score>=90?'A':($score>=75?'B':($score>=60?'C':($score>=40?'D':'E')));
        return [
            'candidate_id'=>$candidateId,'score'=>$score,'grade'=>$grade,
            'verified_field_coverage'=>round($coverage,4),'payload_integrity'=>$integrity,
            'permission_confirmed'=>$permission,'freshness'=>round($freshness,4),
            'evidence_count'=>$fieldCount,
            'publishable'=>$score>=75 && $coverage>=0.5 && $integrity===1 && $permission===1,
        ];
    }

    private function freshness(?string $timestamp): float
    {
        if(!$timestamp) return 0;
        $age=max(0,now()->diffInHours($timestamp));
        return max(0,1-min(1,$age/(24*30)));
    }
}
