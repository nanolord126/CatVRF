<?php

declare(strict_types=1);


namespace App\Domains\Auto\Events;

use App\Domains\Auto\Models\AutoPart;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие обновления остатка автозапчасти.
 * Production 2026.
 */
final class AutoPartStockUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly AutoPart $autoPart,
        public readonly int $oldStock,
        public readonly int $newStock,
        public readonly string $correlationId
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new \Illuminate\Broadcasting\Channel('auto.parts.' . $this->autoPart->tenant_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'AutoPartStockUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'part_id' => $this->autoPart->id,
            'sku' => $this->autoPart->sku,
            'old_stock' => $this->oldStock,
            'new_stock' => $this->newStock,
            'difference' => $this->newStock - $this->oldStock,
            'correlation_id' => $this->correlationId,
        ];
    }
}
