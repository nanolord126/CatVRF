<?php declare(strict_types=1);

namespace App\Domains\OfficeCatering\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CorporateOrderCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $corporateOrderId,
        public readonly int $tenantId,
        public readonly int $userId,
        public readonly int $totalPrice,
        public readonly string $correlationId,
    ) {}
}
