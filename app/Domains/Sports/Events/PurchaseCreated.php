declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Sports\Events;

use App\Domains\Sports\Models\Purchase;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * PurchaseCreated
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class PurchaseCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Purchase $purchase,
        public string $correlationId = '',
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
