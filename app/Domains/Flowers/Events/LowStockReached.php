declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Flowers\Events;

use Illuminate\Foundation\Events\Dispatchable;

final /**
 * LowStockReached
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class LowStockReached
{
    use Dispatchable;

    public function __construct(
        public readonly int $itemId,
        public readonly int $currentStock,
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
