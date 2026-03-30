<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TowingCompleted extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithSockets, SerializesModels;

        public function __construct(
            public readonly TowingRequest $request,
            public readonly string $correlationId
        ) {
            Log::channel('audit')->info('TowingCompleted event dispatched', [
                'correlation_id' => $this->correlationId,
                'request_id' => $this->request->id,
                'dropoff_location' => $this->request->dropoff_location,
            ]);
        }

        public function broadcastOn(): array
        {
            return [
                new PrivateChannel('tenant.' . $this->request->tenant_id),
                new PrivateChannel('user.' . $this->request->client_id),
            ];
        }

        public function broadcastAs(): string
        {
            return 'towing.completed';
        }
}
