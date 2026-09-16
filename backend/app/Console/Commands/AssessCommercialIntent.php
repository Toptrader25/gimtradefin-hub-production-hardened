<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Services\CommercialIntentService;

final class AssessCommercialIntent extends Command {
 protected $signature='leadhunter:intent {candidate_id?} {--entity=}';
 protected $description='Assess evidence-backed commercial intent for a candidate or entity.';
 public function handle(CommercialIntentService $service): int {
  if($id=$this->argument('candidate_id')) { $r=$service->assessCandidate($id); $this->line(json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)); return self::SUCCESS; }
  if($id=$this->option('entity')) { $r=$service->assessEntity($id); $this->line(json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)); return self::SUCCESS; }
  $this->error('Provide candidate_id or --entity=.'); return self::INVALID;
 }
}
