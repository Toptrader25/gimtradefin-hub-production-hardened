<?php
namespace App\Services;

final class EntityNormalizer
{
    public function name(?string $value): string
    {
        $value = trim((string)$value);
        if ($value === '') return '';
        $value = mb_strtolower($value, 'UTF-8');
        $value = str_replace(['&'], [' and '], $value);
        $value = preg_replace('/\b(limited|ltd|ltd\.|llc|inc|inc\.|corp|corp\.|corporation|company|co|co\.|pte|pvt|private|public|sdn bhd|berhad|bhd)\b/u', ' ', $value);
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value);
        return trim(preg_replace('/\s+/u', ' ', $value));
    }

    public function domain(?string $value): string
    {
        $value = trim(mb_strtolower((string)$value, 'UTF-8'));
        if ($value === '') return '';
        if (!str_contains($value, '://')) $value = 'https://' . $value;
        $host = parse_url($value, PHP_URL_HOST) ?: '';
        $host = preg_replace('/^www\./i', '', strtolower($host));
        return rtrim($host, '.');
    }

    public function emailDomain(?string $email): string
    {
        $email = trim(mb_strtolower((string)$email, 'UTF-8'));
        if (!str_contains($email, '@')) return '';
        return $this->domain(substr(strrchr($email, '@'), 1));
    }

    public function phone(?string $value): string
    {
        return preg_replace('/\D+/', '', (string)$value);
    }

    public function registration(?string $value): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9]+/i', '', (string)$value));
    }

    public function address(?string $value): string
    {
        $value = mb_strtolower(trim((string)$value), 'UTF-8');
        return trim(preg_replace('/\s+/u', ' ', preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value)));
    }

    public function tokens(string $normalizedName): array
    {
        return array_values(array_unique(array_filter(explode(' ', $normalizedName), fn($v) => mb_strlen($v) >= 2)));
    }

    public function transliteratedName(?string $value): string
    {
        $value = (string) $value;
        if ($value === '') return '';
        if (class_exists('Transliterator')) {
            $value = \Transliterator::create('Any-Latin; Latin-ASCII')?->transliterate($value) ?? $value;
        }
        return $this->name($value);
    }
}
