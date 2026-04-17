<?php declare(strict_types=1);

namespace App\Domains\B2B\DTOs;

final readonly class CreateApiKeyDto
{
    public function __construct(
        public int $businessGroupId,
        public string $name,
        public array $permissions,
        public ?\DateTime $expiresAt = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            businessGroupId: $data['business_group_id'],
            name: $data['name'],
            permissions: $data['permissions'] ?? [],
            expiresAt: isset($data['expires_at']) ? new \DateTime($data['expires_at']) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'business_group_id' => $this->businessGroupId,
            'name' => $this->name,
            'permissions' => $this->permissions,
            'expires_at' => $this->expiresAt?->format('Y-m-d H:i:s'),
        ];
    }
}
