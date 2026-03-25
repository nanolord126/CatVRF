declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Grocery\Events;

use App\Domains\Grocery\Models\GroceryOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * GroceryOrderCreated
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class GroceryOrderCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly GroceryOrder $order,
        public readonly string $correlationId
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
