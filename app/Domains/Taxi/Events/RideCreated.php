declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Taxi\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * RideCreated
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class RideCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $rideId,
        public readonly string $driverId,
        public readonly string $passengerId,
        public readonly string $correlationId,
        public readonly array $metadata = [],
    ) {
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
}
