<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Services\{SourceScanner,SourceRegistry};

class ScanSources extends Command
{
    protected $signature='source:scan {--source=} {--enabled}';
    protected $description='Scan permitted real sources and store raw evidence/candidates; never publishes leads.';
    public function handle(SourceRegistry $registry, SourceScanner $scanner): int
    {
        $slugs=$this->option('source') ? [$this->option('source')] : array_map(fn($s)=>$s->slug,$registry->enabled());
        if (!$slugs) {$this->warn('No enabled + permission-confirmed sources.'); return self::SUCCESS;}
        foreach($slugs as $slug){ try{$this->info(json_encode($scanner->scan($slug),JSON_PRETTY_PRINT));}catch(\Throwable $e){$this->error($slug.': '.$e->getMessage());} }
        return self::SUCCESS;
    }
}
