<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\SemanticOpportunityService;
class BuildSemanticProfiles extends Command {
 protected $signature='leadhunter:build-semantic-profiles {--entity=} {--limit=500}';
 protected $description='Build normalized multilingual semantic profiles for commercial entities';
 public function handle(SemanticOpportunityService $svc): int { $q=DB::table('entity_commercial_profiles')->select('entity_id'); if($this->option('entity'))$q->where('entity_id',$this->option('entity')); else $q->limit((int)$this->option('limit')); $n=0; foreach($q->pluck('entity_id') as $id){$svc->buildEntityProfile($id);$n++;} $this->info("Built {$n} semantic profiles."); return self::SUCCESS; }
}
