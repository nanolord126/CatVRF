<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Events;

use App\Domains\Bonuses\DTOs\FloatYieldDto;
use App\Domains\Bonuses\Models\FloatYieldTransaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * FloatYieldCredited - Event dispatched when float yield is credited
 * 
 * Triggers listeners for:
 * - Wallet crediting
 * - Notification sending
 * - BigData tracking
 * - Analytics
 */
final class FloatYieldCredited
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly FloatYieldTransaction $transaction,
        public readonly FloatYieldDto $dto,
    ) {}

    public function getTransactionId(): int
    {
        return $this->transaction->id;
    }

    public function getUserId(): int
    {
        return $this->transaction->user_id;
    }

    public function getTenantId(): int
    {
        return $this->transaction->tenant_id;
    }

    public function getUserYield(): float
    {
        return (float) $this->transaction->user_yield;
    }

    public function getPlatformYield(): float
    {
        return (float) $this->transaction->platform_yield;
    }

    public function getTotalYield(): float
    {
        return $this->transaction->getTotalYield();
    }

    public function getYieldRate(): float
    {
        return (float) $this->transaction->yield_rate;
    }

    public function getTotalFloat(): float
    {
        return (float) $this->transaction->total_float;
    }

    public function getCorrelationId(): string
    {
        return $this->dto->correlationId ?? $this->transaction->correlation_id;
    }

    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transaction->id,
            'user_id' => $this->transaction->user_id,
            'tenant_id' => $this->transaction->tenant_id,
            'total_float' => $this->transaction->total_float,
            'platform_yield' => $this->transaction->platform_yield,
            'user_yield' => $this->transaction->user_yield,
            'total_yield' => $this->getTotalYield(),
            'yield_rate' => $this->transaction->yield_rate,
            'yield_date' => $this->transaction->yield_date->toDateString(),
            'correlation_id' => $this->getCorrelationId(),
        ];
    }
}
