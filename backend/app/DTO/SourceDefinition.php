<?php
namespace App\DTO;

final class SourceDefinition
{
    public function __construct(
        public readonly string $slug,
        public readonly string $name,
        public readonly string $class,
        public readonly string $url,
        public readonly string $region,
        public readonly string $role,
        public readonly int $priority = 50,
        public readonly bool $enabled = false,
        public readonly bool $permissionConfirmed = false,
        public readonly bool $requiresAuth = false,
        public readonly ?string $termsUrl = null,
        public readonly array $allowedHosts = [],
        public readonly array $options = [],
    ) {}

    public static function fromArray(string $slug, array $v): self
    {
        return new self(
            $slug, $v['name'], $v['class'], $v['url'], $v['region'] ?? 'Global',
            $v['role'] ?? 'commercial', (int)($v['priority'] ?? 50),
            (bool)($v['enabled'] ?? false), (bool)($v['permission_confirmed'] ?? false),
            (bool)($v['requires_auth'] ?? false), $v['terms_url'] ?? null,
            $v['allowed_hosts'] ?? [], $v['options'] ?? []
        );
    }
}
