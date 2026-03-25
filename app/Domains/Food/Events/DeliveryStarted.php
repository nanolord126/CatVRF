declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Food\Events;

use App\Domains\Food\Models\DeliveryOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event при начале доставки.
 * Production 2026.
 */
final class DeliveryStarted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public DeliveryOrder $delivery,
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
