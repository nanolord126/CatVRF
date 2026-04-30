<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Entities;

use Carbon\CarbonImmutable;
use Modules\Payment\Domain\ValueObjects\Money;

final readonly class EscrowHold
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $tenantId,
        public ?int $businessGroupId,
        public int $paymentIntentId,
        public int $walletId,
        public Money $amount,
        public string $currency,
        public string $status, // held, partially_released, released, canceled
        public array $releaseConditions,
        public ?CarbonImmutable $autoReleaseAt,
        public Money $remainingAmount,
        public Money $releasedAmount,
        public ?string $releaseReason,
        public ?CarbonImmutable $releasedAt,
        public ?CarbonImmutable $canceledAt,
        public string $correlationId,
        public array $metadata,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public function canRelease(int $amountKopecks): bool
    {
        return $this->status === 'held' || $this->status === 'partially_released'
            && $this->remainingAmount->toKopecks() >= $amountKopecks;
    }

    public function isExpired(): bool
    {
        return $this->autoReleaseAt !== null && $this->autoReleaseAt->isBefore(now());
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['held', 'partially_released'], true);
    }
}
