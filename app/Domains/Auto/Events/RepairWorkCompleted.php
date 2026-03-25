<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use App\Domains\Auto\Models\AutoServiceOrder;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Событие: работы завершены (СТО).
 * Production 2026.
 */
final class RepairWorkCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly AutoServiceOrder $order,
        public readonly string $correlationId,
    ) {
        $this->log->channel('audit')->info('Repair work completed', [
            'correlation_id' => $this->correlationId,
            'order_id' => $this->order->id,
            'client_id' => $this->order->client_id,
            'total_price' => $this->order->total_price,
            'tenant_id' => $this->order->tenant_id,
        ]);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("tenant.{$this->order->tenant_id}.auto.repairs"),
            new PrivateChannel("user.{$this->order->client_id}.orders"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'auto.repair.completed';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'vehicle_id' => $this->order->vehicle_id,
            'total_price' => $this->order->total_price,
            'completed_at' => $this->order->completed_at?->toISOString(),
            'correlation_id' => $this->correlationId,
        ];
    }

    public function shouldBroadcast(): bool
    {
        return $this->order->status === 'completed';
    }
}
