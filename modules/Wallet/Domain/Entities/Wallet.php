<?php

declare(strict_types=1);

namespace Modules\Wallet\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class Wallet
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $tenantId,
        public ?int $businessGroupId,
        public int $currentBalance,
        public int $holdAmount,
        public ?string $correlationId,
        public ?array $tags,
        public ?array $metadata,
        public bool $isActive,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public function availableBalance(): int
    {
        return max(0, $this->currentBalance - $this->holdAmount);
    }

    public function hasSufficientBalance(int $amount): bool
    {
        return $this->availableBalance() >= $amount;
    }
}
