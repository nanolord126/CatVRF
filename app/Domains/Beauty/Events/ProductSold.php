declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Events;

use App\Domains\Beauty\Models\BeautyProduct;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * ProductSold
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ProductSold
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly BeautyProduct $product,
        public readonly int $quantity,
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
