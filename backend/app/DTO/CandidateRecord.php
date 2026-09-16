<?php
namespace App\DTO;

final class CandidateRecord
{
    public function __construct(
        public readonly string $externalKey,
        public readonly string $title,
        public readonly ?string $url,
        public readonly ?string $description,
        public readonly ?string $country,
        public readonly ?string $signalType,
        public readonly ?string $publishedAt,
        public readonly array $attributes = [],
    ) {}

    public function toArray(): array
    {
        return [
            'external_key'=>$this->externalKey,
            'title'=>$this->title,
            'url'=>$this->url,
            'description'=>$this->description,
            'country'=>$this->country,
            'signal_type'=>$this->signalType,
            'published_at'=>$this->publishedAt,
            'attributes'=>$this->attributes,
        ];
    }
}
