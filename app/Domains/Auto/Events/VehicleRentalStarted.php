<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class VehicleRentalStarted extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithSockets, SerializesModels;

        public function __construct(
            public readonly VehicleRental $rental,
            public readonly string $correlationId
        ) {
            Log::channel('audit')->info('VehicleRentalStarted event dispatched', [
                'correlation_id' => $this->correlationId,
                'rental_id' => $this->rental->id,
            ]);
        }

        public function broadcastOn(): array
        {
            return [
                new PrivateChannel('tenant.' . $this->rental->tenant_id),
                new PrivateChannel('user.' . $this->rental->renter_id),
            ];
        }

        public function broadcastAs(): string
        {
            return 'rental.started';
        }
}
