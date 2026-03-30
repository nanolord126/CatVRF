<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AutoPartCreated extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithSockets, SerializesModels;

        public function __construct(
            public readonly AutoPart $autoPart,
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
            return 'AutoPartCreated';
        }

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
