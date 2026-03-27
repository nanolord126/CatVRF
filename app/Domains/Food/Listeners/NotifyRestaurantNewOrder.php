<?php

declare(strict_types=1);


namespace App\Domains\Food\Listeners;

use App\Domains\Food\Events\OrderCreated;
use Illuminate\Support\Facades\Log;

final /**
 * NotifyRestaurantNewOrder
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class NotifyRestaurantNewOrder
{
    public function handle(OrderCreated $event): void
    {
        try {
            Log::channel('audit')->info('Restaurant notified of new order', [
                'order_id' => $event->orderId,
                'restaurant_id' => $event->restaurantId,
                'client_id' => $event->clientId,
                'total_amount' => $event->totalAmount,
                'correlation_id' => $event->correlationId,
                'action' => 'order_created_restaurant_notification',
            ]);
            // Notification::send($restaurant, new NewOrderNotification($event));
        } catch (\Exception $e) {
            Log::channel('audit')->error('Failed to notify restaurant', [
                'correlation_id' => $event->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
