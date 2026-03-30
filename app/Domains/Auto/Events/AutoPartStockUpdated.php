<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AutoPartStockUpdated extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
