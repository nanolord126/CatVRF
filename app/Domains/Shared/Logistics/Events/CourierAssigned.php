<?php

declare(strict_types=1);

/**
 * CourierAssigned — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/courierassigned
 */

namespace App\Domains\Logistics\Events;

use Carbon\CarbonImmutable;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Domains\Logistics\Models\OrderShipment;
use App\Domains\Logistics\Models\Courier;

final class CourierAssigned implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        private readonly OrderShipment $shipment,
        private readonly Courier $courier,
        private readonly string $correlationId
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('orders.'.$this->shipment->order_id),
            new PrivateChannel('couriers.'.$this->courier->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'courier.assigned';
    }

    public function broadcastWith(): array
    {
        return [
            'shipment_id' => $this->shipment->id,
            'order_id' => $this->shipment->order_id,
            'courier_id' => $this->courier->id,
            'courier_name' => $this->courier->name ?? 'Courier #'.$this->courier->id,
            'courier_lat' => $this->courier->current_lat,
            'courier_lng' => $this->courier->current_lng,
            'correlation_id' => $this->correlationId,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }
}
