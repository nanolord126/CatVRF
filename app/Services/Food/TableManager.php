<?php

namespace App\Services\Food;

use App\Models\Tenants\RestaurantOrder;
use App\Models\Tenants\RestaurantTable;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Сервис управления залом ресторана.
 * Синхронизирует статусы столов и заказы гостей.
 */
class TableManager
{
    /**
     * Открыть стол для гостей (Начало визита).
     */
    public function openTable(int $tableId, int $guests = 1)
    {
        $table = RestaurantTable::findOrFail($tableId);
        $table->update(['status' => 'occupied']);

        $order = RestaurantOrder::create([
            'table_id' => $tableId,
            'guests_count' => $guests,
            'status' => 'pending', // Еще не заказано
            'kitchen_status' => 'pending',
            'order_type' => 'dine_in',
        ]);

        return $order;
    }

    /**
     * Закрыть стол и выставить чек.
     */
    public function closeTable(int $tableId)
    {
        $table = RestaurantTable::findOrFail($tableId);
        $table->update(['status' => 'cleaning']);

        // Логика печати чека и оплаты...
        
        Notification::make()
            ->title("Стол #{$table->number} готов к уборке")
            ->info()
            ->send();
    }
}
