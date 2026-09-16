<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\LearningGovernanceScaleService;
class BuildSourceScorecards extends Command {protected $signature='leadhunter:source-scorecards {--from=} {--to=}';protected $description='Build source performance scorecards';public function handle(LearningGovernanceScaleService $s):int{$from=$this->option('from')?:now()->subDays(30)->toDateTimeString();$to=$this->option('to')?:now()->toDateTimeString();$sources=DB::table('lh_learning_events')->whereBetween('occurred_at',[$from,$to])->whereNotNull('source')->distinct()->pluck('source');foreach($sources as $source){$r=$s->sourceScorecard($source,$from,$to);DB::table('lh_source_scorecards')->updateOrInsert(['source_key'=>$source,'period_start'=>substr($from,0,10),'period_end'=>substr($to,0,10)],['records_ingested'=>$r['ing'],'verified_records'=>$r['verified'],'qualified_records'=>$r['qual'],'won_records'=>$r['won'],'duplicates'=>$r['dupes'],'risk_flags'=>$r['risk'],'evidence_quality'=>$r['quality'],'conversion_rate'=>$r['conv'],'overall_score'=>$r['quality'],'metrics'=>json_encode($r),'updated_at'=>now()]);} $this->info('Scorecards built: '.$sources->count());return self::SUCCESS;}}
