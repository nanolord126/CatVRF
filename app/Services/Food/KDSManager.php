<?php

namespace App\Services\Food;

use App\Models\Tenants\RestaurantOrder;
use App\Models\Tenants\RestaurantOrderItem;
use Filament\Notifications\Notification;
use Illuminate\Support\{Carbon, Facades};
use Illuminate\Support\Facades\Log;

/**
 * Сервис управления Кухонным Дисплеем (KDS).
 * Обеспечивает мгновенную связь официанта и повара.
 */
class KDSManager
{
    /**
     * Подача заказа "встречкой" (Fired) на кухню.
     * Происходит при подтверждении официантом.
     */
    public function fireOrder(int $orderId)
    {
        $order = RestaurantOrder::findOrFail($orderId);
        $order->update([
            'kitchen_status' => 'queued',
            'fired_at' => Carbon::now(),
            'status' => 'cooking', // Перевод заказа в активное состояние
        ]);

        // Уведомление на станцию поваров (KDS Panel)
        Notification::make()
            ->title("Новый заказ для стола #{$order->table->number}")
            ->body("Кол-во персон: {$order->guests_count}. Курсов: " . count($order->order_sequences ?? [1]))
            ->warning() // Выделение цветом
            ->send();

        Log::info("KDS: Order #{$orderId} fired to kitchen. CorrelationID: {$order->correlation_id}");

        return true;
    }

    /**
     * Перевод конкретного блюда в статус "Готовится"
     */
    public function dishToCooking(int $itemId)
    {
        $item = RestaurantOrderItem::findOrFail($itemId);
        $item->update(['status' => 'cooking']);
        
        return true;
    }

    /**
     * Завершение приготовления блюда. 
     * Если это последнее блюдо в заказе/курсе, уведомляем официанта.
     */
    public function dishReady(int $itemId)
    {
        $item = RestaurantOrderItem::findOrFail($itemId);
        $item->update(['status' => 'ready']);

        $nextPending = RestaurantOrderItem::where('order_id', $item->order_id)
            ->where('status', '!=', 'ready')
            ->count();

        if ($nextPending === 0) {
            $order = $item->order;
            $order->update(['kitchen_status' => 'ready']);
            
            Notification::make()
                ->title("Заказ для стола #{$order->table->number} ГОТОВ!")
                ->success()
                ->send();
        }

        return true;
    }
}
