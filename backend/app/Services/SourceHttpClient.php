<?php
namespace App\Services;

use App\DTO\FetchResult;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

final class SourceHttpClient
{
    public function __construct(private readonly Client $client = new Client()) {}

    public function get(string $url, array $allowedHosts, array $headers = [], int $timeout = 20): FetchResult
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $allowed = array_map('strtolower', $allowedHosts);
        if (!$host || (!$allowed || !in_array($host, $allowed, true))) {
            throw new RuntimeException('Host is not allow-listed for this source.');
        }

        $started = microtime(true);
        try {
            $response = $this->client->request('GET', $url, [
                'timeout'=>$timeout,
                'connect_timeout'=>10,
                'allow_redirects'=>[
                    'max'=>3,
                    'strict'=>true,
                    'on_redirect'=>function($request, $response, $uri) use ($allowed) {
                        $redirectHost = strtolower((string) $uri->getHost());
                        if (!$redirectHost || !in_array($redirectHost, $allowed, true)) {
                            throw new RuntimeException('Redirect target is outside the source allow-list.');
                        }
                    },
                ],
                'headers'=>array_merge([
                    'User-Agent'=>'GiMtradefin-LeadHunter/1.0 (+https://gimtradefin.com; permitted-source-monitor)',
                    'Accept'=>'text/html,application/xhtml+xml,application/xml,application/json;q=0.9,*/*;q=0.5',
                ], $headers),
                'http_errors'=>false,
                'verify'=>true,
            ]);
        } catch (GuzzleException $e) {
            throw new RuntimeException('Source fetch failed: '.$e->getMessage(), 0, $e);
        }
        $duration=(microtime(true)-$started)*1000;
        $h=$response->getHeaders();
        return new FetchResult(
            $response->getStatusCode(),
            (string)$response->getHeaderLine('X-Guzzle-Redirect-History') ?: $url,
            (string)$response->getBody(),
            $response->getHeaderLine('Content-Type') ?: 'application/octet-stream',
            $response->getHeaderLine('ETag') ?: null,
            $response->getHeaderLine('Last-Modified') ?: null,
            $duration,
            $h
        );
    }
}
