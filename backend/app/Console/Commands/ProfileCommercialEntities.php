<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\CommercialIntelligenceService;
class ProfileCommercialEntities extends Command {
 protected $signature='leadhunter:profile-commercial {--entity=} {--limit=100}'; protected $description='Build buyer/seller/partner/investor intelligence profiles from evidence-backed observations.';
 public function handle(CommercialIntelligenceService $service): int { $q=DB::table('entities')->orderByDesc('last_seen_at'); if($this->option('entity')) $q->where('id',$this->option('entity')); $n=0; foreach($q->limit((int)$this->option('limit'))->pluck('id') as $id){ $r=$service->profileEntity($id); $this->line($id.' role='.$r['profile']['primary_role'].' confidence='.$r['profile_confidence']); $n++; } $this->info('Profiled '.$n.' entities.'); return self::SUCCESS; }
}
