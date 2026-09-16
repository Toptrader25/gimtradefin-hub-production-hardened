<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Services\OpportunityMatchingService;
class GenerateOpportunityMatches extends Command {
 protected $signature='leadhunter:match {entity_id} {--limit=25}'; protected $description='Generate explainable commercial opportunity matches for an entity';
 public function handle(OpportunityMatchingService $svc): int { $r=$svc->matchEntity($this->argument('entity_id'),(int)$this->option('limit'));$this->info('Run: '.$r['run_id'].' | Evaluated: '.$r['evaluated'].' | Matches: '.count($r['matches']));foreach($r['matches'] as $m)$this->line($m['score'].'/'.$m['confidence'].' '.$m['recommendation'].' '.$m['explanation']);return self::SUCCESS; }
}
