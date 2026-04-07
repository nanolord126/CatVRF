<?php declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * CourierLocationUpdated — реал-тайм обновление координат курьера.
 *
 * Каналы:
 *  - delivery.{deliveryOrderId}  — для пользователя (трекинг своего заказа)
 *  - courier.{courierId}.location — для диспетчера и самого курьера
 */
final class CourierLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        private readonly int    $courierId,
        private readonly float  $lat,
        private readonly float  $lon,
        private readonly float  $speed,
        private readonly float  $bearing,
        private readonly ?int   $deliveryOrderId,
        private readonly string $correlationId,
    ) {}

    /** @return array<Channel|PresenceChannel> */
    public function broadcastOn(): array
    {
        $channels = [
            new Channel("courier.{$this->courierId}.location"),
        ];

        if ($this->deliveryOrderId !== null) {
            $channels[] = new Channel("delivery.{$this->deliveryOrderId}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'CourierLocationUpdated';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'courier_id'        => $this->courierId,
            'lat'               => $this->lat,
            'lon'               => $this->lon,
            'speed'             => $this->speed,
            'bearing'           => $this->bearing,
            'delivery_order_id' => $this->deliveryOrderId,
            'correlation_id'    => $this->correlationId,
            'timestamp'         => now()->toIso8601String(),
        ];
    }
}
