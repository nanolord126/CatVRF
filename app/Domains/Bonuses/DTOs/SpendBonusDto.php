<?php declare(strict_types=1);

namespace App\Domains\Bonuses\DTOs;

final readonly class SpendBonusDto
{
    public function __construct(
        public int $tenantId,
        public int $userId,
        public int $amount,
        public string $reason = 'checkout',
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            userId: $data['user_id'],
            amount: $data['amount'],
            reason: $data['reason'] ?? 'checkout',
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'amount' => $this->amount,
            'reason' => $this->reason,
        ];
    }
}
