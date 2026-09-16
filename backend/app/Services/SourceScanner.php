<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SourceScanner
{
    public function __construct(private readonly SourceRegistry $registry, private readonly EvidenceService $evidence) {}

    public function scan(string $slug): array
    {
        $source=$this->registry->definition($slug);
        if (!$source->enabled) throw new \RuntimeException("Source is disabled: {$slug}");
        if (!$source->permissionConfirmed) throw new \RuntimeException("Permission/access policy is not confirmed for: {$slug}");
        if ($source->requiresAuth) throw new \RuntimeException("Authenticated source requires its approved API connector configuration: {$slug}");

        $runId=(string)Str::uuid(); $started=microtime(true);
        DB::table('ingestion_runs')->insert(['id'=>$runId,'source_slug'=>$slug,'status'=>'running','started_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        try {
            $connector=$this->registry->connector($source); $fetched=$connector->fetch($source);
            $sha=hash('sha256',$fetched->body);
            $payloadId=(string)Str::uuid();
            DB::table('source_payloads')->insert([
                'id'=>$payloadId,'run_id'=>$runId,'source_slug'=>$slug,'url'=>$source->url,
                'http_status'=>$fetched->status,'content_type'=>substr($fetched->contentType,0,190),'sha256'=>$sha,
                'etag'=>$fetched->etag,'last_modified'=>$fetched->lastModified,'body'=>$fetched->body,
                'fetched_at'=>now(),'created_at'=>now(),'updated_at'=>now(),
            ]);
            $manifestId=$this->evidence->createManifest($runId,$payloadId,[
                'source_slug'=>$slug,'url'=>$source->url,'http_status'=>$fetched->status,
                'content_type'=>substr($fetched->contentType,0,190),'sha256'=>$sha,'body'=>$fetched->body,'fetched_at'=>now(),
            ]);
            $records=$connector->parse($source,$fetched);
            $new=0;
            foreach($records as $r){
                $existing=DB::table('source_candidates')->where('source_slug',$slug)->where('external_key',$r->externalKey)->first();
                if(!$existing){
                    $new++;
                    $candidateId=(string)Str::uuid();
                    DB::table('source_candidates')->insert([
                        'id'=>$candidateId,'run_id'=>$runId,'source_slug'=>$slug,'external_key'=>$r->externalKey,
                        'title'=>$r->title,'url'=>$r->url,'description'=>$r->description,'country'=>$r->country,'signal_type'=>$r->signalType,
                        'published_at'=>$r->publishedAt,'attributes'=>json_encode($r->attributes),'first_seen_at'=>now(),'last_seen_at'=>now(),'created_at'=>now(),'updated_at'=>now()
                    ]);
                } else {
                    $candidateId=$existing->id;
                    DB::table('source_candidates')->where('id',$candidateId)->update(['last_seen_at'=>now(),'updated_at'=>now()]);
                }
                $this->evidence->attachCandidateEvidence($candidateId,$manifestId,[
                    'title'=>$r->title,'description'=>$r->description
                ],$fetched->body);
            }
            DB::table('ingestion_runs')->where('id',$runId)->update(['status'=>'completed','http_status'=>$fetched->status,'records_seen'=>count($records),'new_records'=>$new,'duration_ms'=>(int)((microtime(true)-$started)*1000),'completed_at'=>now(),'updated_at'=>now()]);
            return ['run_id'=>$runId,'source'=>$slug,'status'=>'completed','http_status'=>$fetched->status,'records_seen'=>count($records),'new_records'=>$new,'evidence_manifest_id'=>$manifestId];
        } catch(\Throwable $e){
            DB::table('ingestion_runs')->where('id',$runId)->update(['status'=>'failed','error_message'=>Str::limit($e->getMessage(),1000),'duration_ms'=>(int)((microtime(true)-$started)*1000),'completed_at'=>now(),'updated_at'=>now()]); throw $e;
        }
    }
}
