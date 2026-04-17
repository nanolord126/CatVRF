<?php declare(strict_types=1);

namespace App\Domains\Security\DTOs;

final readonly class CreateApiKeyDto
{
    public function __construct(
        public int $tenantId,
        public string $name,
        public ?array $permissions = null,
        public ?array $ipWhitelist = null,
        public ?\DateTime $expiresAt = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            name: $data['name'],
            permissions: $data['permissions'] ?? null,
            ipWhitelist: $data['ip_whitelist'] ?? null,
            expiresAt: isset($data['expires_at']) ? new \DateTime($data['expires_at']) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'name' => $this->name,
            'permissions' => $this->permissions,
            'ip_whitelist' => $this->ipWhitelist,
            'expires_at' => $this->expiresAt?->format('Y-m-d H:i:s'),
        ];
    }
}
