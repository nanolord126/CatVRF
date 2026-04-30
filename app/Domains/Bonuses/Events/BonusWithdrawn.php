<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Events;

use App\Domains\Bonuses\Models\BonusTransaction;
use App\Domains\Bonuses\DTOs\WithdrawBonusDto;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * BonusWithdrawn - Event dispatched when bonus is withdrawn to real money
 * 
 * Triggers listeners for:
 * - Payment integration
 * - BigData tracking
 * - Notification sending
 * - Compliance logging
 */
final class BonusWithdrawn
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly BonusTransaction $transaction,
        public readonly WithdrawBonusDto $dto,
    ) {}

    public function getTransactionId(): string
    {
        return $this->transaction->id;
    }

    public function getUserId(): string
    {
        return $this->transaction->user_id;
    }

    public function getAmount(): int
    {
        return $this->transaction->amount;
    }

    public function getNetAmount(): int
    {
        return $this->dto->getNetAmount();
    }

    public function getCorrelationId(): ?string
    {
        return $this->transaction->correlation_id;
    }
}
