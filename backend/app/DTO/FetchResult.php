<?php
namespace App\DTO;

final class FetchResult
{
    public function __construct(
        public readonly int $status,
        public readonly string $url,
        public readonly string $body,
        public readonly string $contentType = 'application/octet-stream',
        public readonly ?string $etag = null,
        public readonly ?string $lastModified = null,
        public readonly float $durationMs = 0,
        public readonly array $headers = [],
    ) {}
}
