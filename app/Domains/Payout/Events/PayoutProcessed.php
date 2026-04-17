<?php declare(strict_types=1);

namespace App\Domains\Payout\Events;

use App\Domains\Payout\Models\PayoutRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PayoutProcessed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly PayoutRequest $payoutRequest,
        public readonly string $correlationId,
    ) {}
}
