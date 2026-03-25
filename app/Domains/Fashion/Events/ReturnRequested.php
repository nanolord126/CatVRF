declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Fashion\Events;

use App\Domains\Fashion\Models\FashionReturn;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * ReturnRequested
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ReturnRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public FashionReturn $return,
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
