declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use App\Domains\Auto\Models\CarWashBooking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final /**
 * CarWashBookingCreated
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CarWashBookingCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly CarWashBooking $booking,
        public readonly string $correlationId
    ) {
        $this->log->channel('audit')->info('CarWashBookingCreated event dispatched', [
            'correlation_id' => $this->correlationId,
            'booking_id' => $this->booking->id,
        ]);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->booking->tenant_id),
            new PrivateChannel('user.' . $this->booking->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'car-wash.booking.created';
    }
}
