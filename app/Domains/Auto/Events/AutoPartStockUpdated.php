<?php

declare(strict_types=1);

namespace App\Domains\Auto\Events;

use Psr\Log\LoggerInterface;
use App\Domains\Auto\Models\AutoPart;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class AutoPartStockUpdated
 *
 * Part of the Auto vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see Dispatchable
 */
final class AutoPartStockUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly AutoPart $autoPart,
        public readonly int $oldStock,
        public readonly int $newStock,
        public readonly string $correlationId,
        public readonly LoggerInterface $logger
    ) {
        $this->logger->$this->logger->info('AutoPart stock updated event created', [
            'auto_part_id' => $this->autoPart->id,
            'old_stock' => $this->oldStock,
            'new_stock' => $this->newStock,
            'correlation_id' => $this->correlationId,
        ]);
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.'.$this->autoPart->tenant_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'auto.part.stock.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'auto_part_id' => $this->autoPart->id,
            'uuid' => $this->autoPart->uuid,
            'old_stock' => $this->oldStock,
            'new_stock' => $this->newStock,
            'correlation_id' => $this->correlationId,        ];
    }
}
