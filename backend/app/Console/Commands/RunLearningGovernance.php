<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Services\LearningGovernanceScaleService;
class RunLearningGovernance extends Command {protected $signature='leadhunter:learning-governance {--from=} {--to=}';protected $description='Run Stage 10 quality, scorecard and governance checks';public function handle(LearningGovernanceScaleService $s):int{$from=$this->option('from')?:now()->subDays(30)->toDateTimeString();$to=$this->option('to')?:now()->toDateTimeString();$this->info(json_encode(['quality'=>$s->qualityCheck(),'period'=>[$from,$to]],JSON_PRETTY_PRINT));return self::SUCCESS;}}
