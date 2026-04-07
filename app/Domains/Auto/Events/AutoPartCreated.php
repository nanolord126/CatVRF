<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use App\Domains\Auto\Models\AutoPart;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AutoPartCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly AutoPart $autoPart,
        public readonly string $correlationId
    ) {

    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('auto.parts.' . $this->autoPart->tenant_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'AutoPartCreated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'part_id' => $this->autoPart->id,
            'sku' => $this->autoPart->sku,
            'name' => $this->autoPart->name,
            'current_stock' => $this->autoPart->current_stock,
            'correlation_id' => $this->correlationId,
        ];
    }
}
