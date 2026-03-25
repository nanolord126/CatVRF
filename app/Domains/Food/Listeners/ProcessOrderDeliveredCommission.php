declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Food\Listeners;

use App\Domains\Food\Events\OrderDelivered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final /**
 * ProcessOrderDeliveredCommission
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ProcessOrderDeliveredCommission
{
    public function handle(OrderDelivered $event): void
    {
        try {
            $this->db->transaction(function () use ($event) {
                $this->log->channel('audit')->info('Order delivery commission processed', [
                    'order_id' => $event->orderId,
                    'restaurant_id' => $event->restaurantId,
                    'delivery_amount' => $event->deliveryAmount,
                    'correlation_id' => $event->correlationId,
                    'action' => 'order_delivered_commission',
                ]);
                // PayoutService::process($restaurant_id, $event->deliveryAmount);
            });
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Failed to process order delivery commission', [
                'correlation_id' => $event->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
