<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Events;

use App\Domains\Bonuses\Models\BonusTransaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * BonusUnlocked - Event dispatched when bonus hold period expires
 * 
 * Triggers listeners for:
 * - Notification sending
 * - BigData tracking
 * - Wallet integration
 */
final class BonusUnlocked
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly BonusTransaction $transaction,
    ) {}

    public function getTransactionId(): string
    {
        return (string) $this->transaction->id;
    }

    public function getUserId(): string
    {
        return (string) $this->transaction->user_id;
    }

    public function getAmount(): int
    {
        return $this->transaction->amount;
    }

    public function getCorrelationId(): ?string
    {
        return $this->transaction->correlation_id;
    }
}
