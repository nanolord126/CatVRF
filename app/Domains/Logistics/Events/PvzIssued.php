<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Events;

use Carbon\CarbonImmutable;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PVZ Issued Event
 *
 * Broadcasted when an order is issued to a pickup point.
 * Enables real-time notification to the user about pickup details.
 */
final class PvzIssued implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $orderId,
        public readonly int $shipmentId,
        public readonly int $pvzId,
        public readonly string $pvzName,
        public readonly string $pvzAddress,
        public readonly string $pickupCode,
        public readonly string $qrCode,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('orders.'.$this->orderId),
            new PrivateChannel('users.'.auth()->id()),
        ];
    }

    public function broadcastAs(): string
    {
        return 'pvz.issued';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->orderId,
            'shipment_id' => $this->shipmentId,
            'pvz_id' => $this->pvzId,
            'pvz_name' => $this->pvzName,
            'pvz_address' => $this->pvzAddress,
            'pickup_code' => $this->pickupCode,
            'qr_code' => $this->qrCode,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }
}
