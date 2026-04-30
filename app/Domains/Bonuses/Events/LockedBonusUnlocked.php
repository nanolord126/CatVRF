<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Events;

use App\Domains\Bonuses\Models\LockedBonusBatch;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * LockedBonusUnlocked - Event dispatched when locked bonuses are unlocked
 * 
 * Triggers listeners for:
 * - Wallet crediting
 * - Notification sending
 * - BigData tracking
 * - Audit logging
 */
final class LockedBonusUnlocked
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly LockedBonusBatch $batch,
        public readonly float $unlockedAmount,
        public readonly bool $instant = false,
        public readonly ?float $instantPrice = null,
    ) {}

    public function getBatchId(): int
    {
        return $this->batch->id;
    }

    public function getUserId(): int
    {
        return $this->batch->user_id;
    }

    public function getTenantId(): int
    {
        return $this->batch->tenant_id;
    }

    public function getRemainingLocked(): float
    {
        return (float) $this->batch->remaining_locked;
    }

    public function getCorrelationId(): string
    {
        return $this->batch->correlation_id;
    }

    public function isFullyVested(): bool
    {
        return $this->batch->isFullyVested();
    }

    public function toArray(): array
    {
        return [
            'batch_id' => $this->batch->id,
            'user_id' => $this->batch->user_id,
            'tenant_id' => $this->batch->tenant_id,
            'unlocked_amount' => $this->unlockedAmount,
            'remaining_locked' => $this->batch->remaining_locked,
            'instant' => $this->instant,
            'instant_price' => $this->instantPrice,
            'is_fully_vested' => $this->isFullyVested(),
            'source' => $this->batch->source,
            'correlation_id' => $this->batch->correlation_id,
        ];
    }
}
