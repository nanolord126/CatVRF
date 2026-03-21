<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PharmacyOrderCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $pharmacyOrderId,
        public readonly int $tenantId,
        public readonly int $userId,
        public readonly int $totalPrice,
        public readonly string $correlationId,
    ) {}
}
