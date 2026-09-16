<?php
namespace App\Services\Connectors;

use App\Contracts\SourceConnector;
use App\DTO\{SourceDefinition,FetchResult,CandidateRecord};
use App\Services\SourceHttpClient;
use Symfony\Component\DomCrawler\Crawler;

final class PublicHtmlConnector implements SourceConnector
{
    public function __construct(private readonly SourceHttpClient $http) {}
    public function fetch(SourceDefinition $source): FetchResult { return $this->http->get($source->url,$source->allowedHosts); }

    public function parse(SourceDefinition $source, FetchResult $result): array
    {
        if ($result->status<200 || $result->status>=300 || !str_contains(strtolower($result->contentType),'text/html')) return [];
        $crawler=new Crawler($result->body,$result->url); $out=[];
        // 1) Prefer configured selectors. These are intentionally source-specific and easy to change without code edits.
        $o=$source->options;
        if (!empty($o['item_selector'])) {
            foreach ($crawler->filter($o['item_selector']) as $node) {
                $c=new Crawler($node);
                $title=$this->text($c,$o['title_selector'] ?? 'h2,h3,h4,a');
                $href=$this->href($c,$o['link_selector'] ?? 'a');
                if (!$title) continue;
                $out[]=new CandidateRecord(sha1(($href ?: '').'|'.$title),$title,$href,$this->text($c,$o['description_selector'] ?? null),$this->text($c,$o['country_selector'] ?? null),$o['signal_type'] ?? 'commercial_signal',$this->text($c,$o['date_selector'] ?? null),['parser'=>'configured_css']);
            }
            if ($out) return $out;
        }
        // 2) JSON-LD ItemList/Article/Product evidence, which is safer than arbitrary scraping.
        $crawler->filter('script[type="application/ld+json"]')->each(function(Crawler $n) use (&$out,$o) {
            $json=trim($n->text('')); $data=json_decode($json,true); if (!$data) return;
            $items=[];
            if (($data['@type'] ?? null)==='ItemList') $items=$data['itemListElement'] ?? [];
            elseif (isset($data['@graph'])) foreach ($data['@graph'] as $g) if (($g['@type'] ?? null)==='ItemList') $items=array_merge($items,$g['itemListElement'] ?? []);
            foreach ($items as $it) { $x=$it['item'] ?? $it; if (!is_array($x)) continue; $title=$x['name'] ?? ''; $url=$x['url'] ?? null; if (!$title) continue; $out[]=new CandidateRecord(sha1(($url ?: '').'|'.$title),trim($title),$url,$x['description'] ?? null,$x['address']['addressCountry'] ?? null,$o['signal_type'] ?? 'commercial_signal',$x['datePublished'] ?? null,['parser'=>'jsonld']); }
        });
        if ($out) return $out;
        // 3) Conservative heuristic fallback: only anchors whose text contains explicit commercial intent phrases.
        $phrases=['we buy','buying lead','buy request','rfq','seeking supplier','looking for supplier','wanted supplier','distributor wanted','partner wanted','sourcing'];
        $crawler->filter('a[href]')->each(function(Crawler $a) use (&$out,$phrases,$o) {
            $title=trim(preg_replace('/\\s+/',' ',$a->text(''))); if (mb_strlen($title)<8 || mb_strlen($title)>240) return;
            $low=mb_strtolower($title); $hit=false; foreach($phrases as $p) if(str_contains($low,$p)){$hit=true;break;} if(!$hit)return;
            $href=$a->attr('href'); if(!$href)return; $url=rtrim($o['base_url'] ?? '','/').'/'.ltrim($href,'/'); if(str_starts_with($href,'http'))$url=$href;
            $out[]=new CandidateRecord(sha1($url),$title,$url,null,null,$o['signal_type'] ?? 'buyer_request',null,['parser'=>'intent_link_heuristic']);
        });
        return array_slice($out,0,(int)($o['max_records'] ?? 200));
    }
    private function text(Crawler $c, ?string $selector): ?string { if(!$selector)return null; try{$v=trim(preg_replace('/\\s+/',' ',$c->filter($selector)->first()->text('')));return $v?:null;}catch(\Throwable){return null;} }
    private function href(Crawler $c, string $selector): ?string { try{$v=$c->filter($selector)->first()->attr('href');return $v?:null;}catch(\Throwable){return null;} }
}
