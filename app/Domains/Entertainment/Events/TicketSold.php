declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Events;

use App\Domains\Entertainment\Models\TicketSale;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * TicketSold
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class TicketSold
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public TicketSale $ticket,
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
