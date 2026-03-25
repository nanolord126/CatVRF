declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\FreshProduce\Events;

use App\Domains\FreshProduce\Models\ProduceOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * BoxDelivered
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class BoxDelivered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ProduceOrder $order,
        public readonly string $correlationId,
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
