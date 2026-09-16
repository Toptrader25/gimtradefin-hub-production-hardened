<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EvidenceService;
use Illuminate\Support\Facades\DB;

class VerifyEvidence extends Command
{
    protected $signature='leadhunter:evidence-verify {manifest? : Evidence manifest UUID}';
    protected $description='Verify Stage 2 evidence manifests against stored source payloads.';

    public function handle(EvidenceService $evidence): int
    {
        $id=$this->argument('manifest');
        $ids=$id ? collect([$id]) : DB::table('evidence_manifests')->pluck('id');
        foreach($ids as $manifestId){
            $result=$evidence->verifyManifest($manifestId);
            $this->line($manifestId.' '.$result['status'].' — '.$result['details']);
        }
        return self::SUCCESS;
    }
}
