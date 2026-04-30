<?php

declare(strict_types=1);

namespace App\Events\Logistics;

use App\Domains\Logistics\Models\Courier;
use App\Domains\Logistics\Models\OrderShipment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * CourierAssigned — событие назначения курьера
 *
 * Используется для:
 * - Broadcasting в реал-тайм
 * - Логирования в ClickHouse для ML
 * - Аналитики
 */
final class CourierAssigned implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly OrderShipment $shipment,
        public readonly Courier $courier,
        public readonly float $distance,
        public readonly int $predictedEta,
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
            'courier_name' => $this->courier->user->name ?? null,
            'vehicle_type' => $this->courier->vehicle_type,
            'distance_m' => $this->distance,
            'eta_minutes' => $this->predictedEta,
            'status' => $this->shipment->status,
        ];
    }
}
