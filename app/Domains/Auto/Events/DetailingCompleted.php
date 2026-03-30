<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DetailingCompleted extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithSockets, SerializesModels;

        public function __construct(
            public readonly CarDetailing $detailing,
            public readonly string $correlationId
        ) {
            Log::channel('audit')->info('DetailingCompleted event dispatched', [
                'correlation_id' => $this->correlationId,
                'detailing_id' => $this->detailing->id,
            ]);
        }

        public function broadcastOn(): array
        {
            return [
                new PrivateChannel('tenant.' . $this->detailing->tenant_id),
                new PrivateChannel('user.' . $this->detailing->client_id),
            ];
        }

        public function broadcastAs(): string
        {
            return 'detailing.completed';
        }
}
