declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Travel\Events;

use App\Domains\Travel\Models\TravelFlight;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * FlightBooked
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class FlightBooked implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public TravelFlight $flight,
        public string $correlationId,
    ) {
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('travel.flights'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'flight.booked';
    }
}
