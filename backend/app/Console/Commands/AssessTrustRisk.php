<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Services\TrustRiskService;

class AssessTrustRisk extends Command {
    protected $signature='leadhunter:assess-risk {type : entity|candidate} {id}';
    protected $description='Assess a Lead Hunter entity or candidate with the Stage 4 Trust & Risk Engine';
    public function handle(TrustRiskService $service): int {
        $type=$this->argument('type'); $id=$this->argument('id');
        if(!in_array($type,['entity','candidate'],true)){ $this->error('Type must be entity or candidate'); return self::INVALID; }
        $result=$type==='entity'?$service->assessEntity($id):$service->assessCandidate($id);
        $this->line(json_encode($result,JSON_PRETTY_PRINT)); return self::SUCCESS;
    }
}
