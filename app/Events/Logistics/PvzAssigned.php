<?php

declare(strict_types=1);

namespace App\Events\Logistics;

use App\Domains\Logistics\Models\OrderShipment;
use App\Domains\Logistics\Models\PickupPoint;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PvzAssigned — событие назначения ПВЗ
 *
 * Используется для:
 * - Broadcasting в реал-тайм
 * - Логирования в ClickHouse для ML
 * - Аналитики
 */
final class PvzAssigned implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly OrderShipment $shipment,
        public readonly PickupPoint $pickupPoint,
        public readonly float $distance,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('orders.'.$this->shipment->order_id),
            new PrivateChannel('pvz.'.$this->pickupPoint->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'pvz.assigned';
    }

    public function broadcastWith(): array
    {
        return [
            'shipment_id' => $this->shipment->id,
            'order_id' => $this->shipment->order_id,
            'pvz_id' => $this->pickupPoint->id,
            'pvz_name' => $this->pickupPoint->name,
            'pvz_address' => $this->pickupPoint->address,
            'distance_m' => $this->distance,
            'status' => $this->shipment->status,
        ];
    }
}
