<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Events;

use App\Domains\Bonuses\DTOs\AwardLockedBonusDto;
use App\Domains\Bonuses\Models\LockedBonusBatch;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * BonusBatchAwarded - Event dispatched when a locked bonus batch is awarded
 * 
 * Triggers listeners for:
 * - Audit logging
 * - BigData tracking
 * - Notification sending
 * - Streak calculation
 */
final class BonusBatchAwarded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly LockedBonusBatch $batch,
        public readonly AwardLockedBonusDto $dto,
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

    public function getOriginalAmount(): float
    {
        return (float) $this->batch->original_amount;
    }

    public function getSource(): string
    {
        return $this->batch->source;
    }

    public function getVestedUntil(): string
    {
        return $this->batch->vested_until->toDateString();
    }

    public function getDailyUnlockRate(): float
    {
        return (float) $this->batch->daily_unlock_rate;
    }

    public function getCorrelationId(): string
    {
        return $this->dto->correlationId ?? $this->batch->correlation_id;
    }

    public function toArray(): array
    {
        return [
            'batch_id' => $this->batch->id,
            'user_id' => $this->batch->user_id,
            'tenant_id' => $this->batch->tenant_id,
            'original_amount' => $this->batch->original_amount,
            'remaining_locked' => $this->batch->remaining_locked,
            'daily_unlock_rate' => $this->batch->daily_unlock_rate,
            'vested_until' => $this->batch->vested_until->toDateString(),
            'source' => $this->batch->source,
            'vesting_curve' => $this->dto->vestingCurve->getType(),
            'correlation_id' => $this->getCorrelationId(),
            'source_type' => $this->dto->sourceType,
            'source_id' => $this->dto->sourceId,
        ];
    }
}
