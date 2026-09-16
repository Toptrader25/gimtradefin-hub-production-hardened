<?php
namespace App\Services\Connectors;

use App\Contracts\SourceConnector;
use App\DTO\{SourceDefinition,FetchResult,CandidateRecord};
use App\Services\SourceHttpClient;

final class JsonApiConnector implements SourceConnector
{
    public function __construct(private readonly SourceHttpClient $http) {}
    public function fetch(SourceDefinition $source): FetchResult
    {
        $headers=[]; $tokenEnv=$source->options['token_env'] ?? null; if ($tokenEnv && ($token=getenv($tokenEnv))) $headers['Authorization']='Bearer '.$token;
        return $this->http->get($source->url,$source->allowedHosts,$headers);
    }
    public function parse(SourceDefinition $source, FetchResult $result): array
    {
        if ($result->status<200 || $result->status>=300) return [];
        $data=json_decode($result->body,true); if (!is_array($data)) return [];
        $items=$data[$source->options['items_path'] ?? 'data'] ?? $data;
        if (!is_array($items)) return [];
        $out=[]; foreach ($items as $item) { if (!is_array($item)) continue;
            $title=(string)($item[$source->options['title_field'] ?? 'title'] ?? ''); if (!$title) continue;
            $url=isset($item[$source->options['url_field'] ?? 'url']) ? (string)$item[$source->options['url_field'] ?? 'url'] : null;
            $out[]=new CandidateRecord(sha1($url ?: json_encode($item)), $title, $url, $item[$source->options['description_field'] ?? 'description'] ?? null, $item[$source->options['country_field'] ?? 'country'] ?? null, $source->options['signal_type'] ?? null, $item[$source->options['published_field'] ?? 'published_at'] ?? null, ['source_format'=>'json','raw'=>$item]);
        } return $out;
    }
}
