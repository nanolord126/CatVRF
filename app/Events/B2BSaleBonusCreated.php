<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class B2BSaleBonusCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $managerId,
        public readonly string $referenceType,
        public readonly int $referenceId,
        public readonly float $saleAmount,
        public readonly int $tenantId,
        public readonly int $verticalId,
        public readonly ?int $orderId = null
    ) {}
}
