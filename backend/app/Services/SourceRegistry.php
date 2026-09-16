<?php
namespace App\Services;

use App\Contracts\SourceConnector;
use App\DTO\SourceDefinition;
use App\Services\Connectors\{RssAtomConnector,JsonApiConnector,PublicHtmlConnector};
use RuntimeException;

final class SourceRegistry
{
    public function __construct(private readonly SourceHttpClient $http) {}
    public function definition(string $slug): SourceDefinition
    {
        $cfg=config("sources.{$slug}"); if (!$cfg) throw new RuntimeException("Unknown source: {$slug}");
        return SourceDefinition::fromArray($slug,$cfg);
    }
    public function connector(SourceDefinition $source): SourceConnector
    {
        return match($source->class) {
            'rss_atom'=>new RssAtomConnector($this->http),
            'json_api'=>new JsonApiConnector($this->http),
            'public_html'=>new PublicHtmlConnector($this->http),
            default=>throw new RuntimeException('Unsupported connector: '.$source->class),
        };
    }
    public function enabled(): array
    {
        $out=[]; foreach(config('sources',[]) as $slug=>$cfg) if(($cfg['enabled']??false) && ($cfg['permission_confirmed']??false)) $out[]=SourceDefinition::fromArray($slug,$cfg); return $out;
    }
}
