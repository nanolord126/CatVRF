declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\FarmDirect\Events;

use App\Domains\FarmDirect\Models\FarmOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * FarmOrderShipped
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class FarmOrderShipped
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly FarmOrder $order,
        public readonly string    $correlationId,
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
