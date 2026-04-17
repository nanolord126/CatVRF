<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Domain\Events;

use App\Domains\RealEstate\Models\PropertyTransaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class EscrowFundsRefunded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly PropertyTransaction $transaction,
        public readonly string $correlationId,
    ) {}
}
