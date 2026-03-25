declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Logistics\Events;

use App\Domains\Logistics\Models\CourierService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * CourierAssigned
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CourierAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public CourierService $courier,
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
