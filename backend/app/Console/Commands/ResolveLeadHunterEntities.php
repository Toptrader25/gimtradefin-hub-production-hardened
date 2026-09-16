<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\EntityResolutionService;

class ResolveLeadHunterEntities extends Command
{
    protected $signature='leadhunter:resolve-entities {--source= : Only candidates from a source} {--limit=100 : Maximum candidates}';
    protected $description='Resolve Lead Hunter candidates to canonical business entities.';
    public function handle(EntityResolutionService $resolver): int
    {
        $q=DB::table('source_candidates')->orderBy('last_seen_at');
        if($this->option('source'))$q->where('source_slug',$this->option('source'));
        $rows=$q->limit((int)$this->option('limit'))->get(); $linked=0;
        foreach($rows as $row){try{$r=$resolver->resolveCandidate($row->id);$linked += $r['linked']?1:0;$this->line($row->id.' → '.($r['top']['entity_id']??'none').' '.($r['top']['decision']??''));}catch(\Throwable $e){$this->error($row->id.' '.$e->getMessage());}}
        $this->info("Processed {$rows->count()} candidates; linked/new: {$linked}."); return self::SUCCESS;
    }
}
