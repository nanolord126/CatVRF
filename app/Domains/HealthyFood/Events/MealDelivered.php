declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\HealthyFood\Events;

use App\Domains\HealthyFood\Models\MealSubscription;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * MealDelivered
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class MealDelivered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly MealSubscription $subscription,
        public readonly string           $correlationId,
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
