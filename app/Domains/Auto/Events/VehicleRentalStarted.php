declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use App\Domains\Auto\Models\VehicleRental;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final /**
 * VehicleRentalStarted
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class VehicleRentalStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly VehicleRental $rental,
        public readonly string $correlationId
    ) {
        $this->log->channel('audit')->info('VehicleRentalStarted event dispatched', [
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
