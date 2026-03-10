<?php

namespace App\Services\Food;

use App\Models\Tenants\RestaurantOrder;
use Filament\Notifications\Notification;
use Illuminate\Support\{Carbon, Facades};
use Illuminate\Support\Facades\Log;

/**
 * Сервис управления процессами на кухне ресторана (HoReCa).
 */
class KitchenProcessManager
{
    public function startCooking(RestaurantOrder $order)
    {
        $order->update([
            'status' => 'cooking',
            'preparation_started_at' => Carbon::now(),
            'correlation_id' => request()->header('X-Correlation-ID', uniqid()),
        ]);

        // Отправка в модуль Staff (Задачи для поваров)
        Log::info("Kitchen: Cooking started for Order #{$order->id}");
        
        return true;
    }

    public function completeDish(RestaurantOrder $order)
    {
        $order->update(['status' => 'ready_for_pickup']);
        
        // Автоматический вызов курьера через модуль Такси/Доставка
        Notification::make()
            ->title('Блюдо готово на раздаче')
            ->success()
            ->send();
            
        return true;
    }
}
