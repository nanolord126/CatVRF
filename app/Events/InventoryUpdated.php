<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * InventoryUpdated Event
 *
 * Broadcasts real-time inventory updates via WebSocket/Reverb
 * when inventory levels change due to sales, restocking, or adjustments.
 *
 * @author CatVRF Team
 * @version 2026.04.26
 */
final class InventoryUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly int $inventoryItemId,
        public readonly int $tenantId,
        public readonly array $changes,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->tenantId . '.inventory'),
            new Channel('inventory.' . $this->inventoryItemId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'inventory.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'inventory_item_id' => $this->inventoryItemId,
            'tenant_id' => $this->tenantId,
            'changes' => $this->changes,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Determine if the event should be broadcast.
     */
    public function broadcastWhen(): bool
    {
        return config('broadcasting.default') !== 'log';
    }
}
