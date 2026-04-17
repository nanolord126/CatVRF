<?php declare(strict_types=1);

namespace App\Domains\Bonuses\DTOs;

final readonly class AwardBonusDto
{
    public function __construct(
        public int $tenantId,
        public int $userId,
        public int $amount,
        public string $type = 'loyalty',
        public ?string $sourceType = null,
        public ?int $sourceId = null,
        public ?array $metadata = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            userId: $data['user_id'],
            amount: $data['amount'],
            type: $data['type'] ?? 'loyalty',
            sourceType: $data['source_type'] ?? null,
            sourceId: $data['source_id'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'amount' => $this->amount,
            'type' => $this->type,
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
            'metadata' => $this->metadata,
        ];
    }
}
