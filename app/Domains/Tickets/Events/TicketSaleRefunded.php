declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Tickets\Events;

use App\Domains\Tickets\Models\TicketSale;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * TicketSaleRefunded
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class TicketSaleRefunded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public TicketSale $ticketSale,
        public string $reason = '',
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
