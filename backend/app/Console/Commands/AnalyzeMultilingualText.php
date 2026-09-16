<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Services\MultilingualIntelligenceService;

class AnalyzeMultilingualText extends Command {
 protected $signature='leadhunter:analyze-language {asset_type} {asset_id} {text} {--language=} {--translate}';
 protected $description='Detect language, extract multilingual commercial concepts, and optionally translate a Lead Hunter text asset.';
 public function handle(MultilingualIntelligenceService $service): int { $r=$service->analyze($this->argument('asset_type'),$this->argument('asset_id'),$this->argument('text'),$this->option('language')); $this->info(json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)); if($this->option('translate')){ $t=$service->translate($r['text_asset_id'],config('multilingual.default_target','en')); $this->info(json_encode($t,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)); } return self::SUCCESS; }
}
