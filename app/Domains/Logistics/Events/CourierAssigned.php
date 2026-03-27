<?php declare(strict_types=1);

namespace App\Domains\Logistics\Events;

use App\Domains\Logistics\Models\Courier;
use App\Domains\Logistics\Models\DeliveryOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Courier Assigned Event (2026 Edition)
 */
final class CourierAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly DeliveryOrder $order,
        public readonly Courier $courier,
        public readonly string $correlationId
    ) {}
}
