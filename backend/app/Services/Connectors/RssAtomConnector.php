<?php
namespace App\Services\Connectors;

use App\Contracts\SourceConnector;
use App\DTO\{SourceDefinition,FetchResult,CandidateRecord};
use App\Services\SourceHttpClient;
use SimpleXMLElement;

final class RssAtomConnector implements SourceConnector
{
    public function __construct(private readonly SourceHttpClient $http) {}
    public function fetch(SourceDefinition $source): FetchResult { return $this->http->get($source->url,$source->allowedHosts,['Accept'=>'application/rss+xml, application/atom+xml, application/xml']); }
    public function parse(SourceDefinition $source, FetchResult $result): array
    {
        if ($result->status < 200 || $result->status >= 300) return [];
        libxml_use_internal_errors(true); $xml=simplexml_load_string($result->body); if (!$xml) return [];
        $out=[];
        if (isset($xml->channel->item)) foreach ($xml->channel->item as $item) {
            $title=trim((string)$item->title); $link=trim((string)$item->link); if (!$title) continue;
            $out[]=new CandidateRecord(sha1($link ?: $title),$title,$link,trim((string)$item->description) ?: null,null,$source->options['signal_type'] ?? null,trim((string)$item->pubDate) ?: null,['source_format'=>'rss']);
        }
        if (isset($xml->entry)) foreach ($xml->entry as $entry) {
            $title=trim((string)$entry->title); $link=''; if (isset($entry->link)) { foreach ($entry->link as $l) { $href=(string)$l['href']; if ($href) {$link=$href;break;} } }
            if (!$title) continue;
            $out[]=new CandidateRecord(sha1($link ?: $title),$title,$link ?: null,trim((string)$entry->summary) ?: null,null,$source->options['signal_type'] ?? null,trim((string)$entry->updated) ?: trim((string)$entry->published) ?: null,['source_format'=>'atom']);
        }
        return $out;
    }
}
