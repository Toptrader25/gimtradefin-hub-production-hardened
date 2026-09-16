<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Services\VerificationWorkflowService;

class RunVerificationWorkflow extends Command
{
 protected $signature='leadhunter:verification-case {entity_id} {--match=} {--reviewer=}';
 protected $description='Create a human verification case for a Lead Hunter entity/opportunity.';
 public function handle(VerificationWorkflowService $svc): int { $id=$svc->createCase(['entity_id'=>$this->argument('entity_id'),'match_id'=>$this->option('match'),'assigned_to'=>$this->option('reviewer')?:null]); $this->info('Verification case created: '.$id); return self::SUCCESS; }
}
