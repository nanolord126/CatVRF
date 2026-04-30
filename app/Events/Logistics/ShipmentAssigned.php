<?php

declare(strict_types=1);

namespace App\Events\Logistics;

use App\Domains\Logistics\Models\OrderShipment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ShipmentAssigned — событие назначения фулфилмента для заказа
 *
 * Диспетчится при назначении курьера, такси или ПВЗ.
 * Используется для реалтайм-обновлений в UI через Laravel Echo.
 */
final class ShipmentAssigned implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly OrderShipment $shipment,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('orders.'.$this->shipment->order_id),
            new PrivateChannel('shipments.'.$this->shipment->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'shipment.assigned';
    }

    /**
     * Additional data to broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'shipment_id' => $this->shipment->id,
            'order_id' => $this->shipment->order_id,
            'fulfillment_type' => $this->shipment->fulfillment_type,
            'fulfillment_id' => $this->shipment->fulfillment_id,
            'status' => $this->shipment->status,
            'eta_minutes' => $this->shipment->eta_minutes,
            'courier_id' => $this->shipment->courier_id,
            'pickup_point_id' => $this->shipment->pickup_point_id,
        ];
    }
}
