<?php declare(strict_types=1);

namespace App\Domains\B2B\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class B2BOrderCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $orderId,
        public readonly int $businessGroupId,
        public readonly int $total,
        public readonly string $correlationId,
    ) {}
}
