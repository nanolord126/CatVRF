declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Events;

use App\Domains\Entertainment\Models\EntertainmentEvent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * EventCancelled
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EventCancelled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public EntertainmentEvent $event,
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
}
