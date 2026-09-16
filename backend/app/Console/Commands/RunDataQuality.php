<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\LearningGovernanceScaleService;
class RunDataQuality extends Command {protected $signature='leadhunter:data-quality {--scope=all}';protected $description='Run Stage 10 data quality checks';public function handle(LearningGovernanceScaleService $s):int{$r=$s->qualityCheck(['scope'=>$this->option('scope')]);DB::table('lh_data_quality_runs')->insert(['id'=>Str::uuid()->toString(),'scope'=>$this->option('scope'),'run_type'=>'scheduled','status'=>'completed','rows_checked'=>0,'errors'=>count($r['issues']),'warnings'=>0,'quality_score'=>$r['quality_score'],'checks'=>json_encode($r),'remediation'=>json_encode([]),'started_at'=>now(),'completed_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);$this->info(json_encode($r,JSON_PRETTY_PRINT));return self::SUCCESS;}}
