<?php

namespace App\Services\Food;

use App\Models\Tenants\RestaurantTable;
use App\Models\Tenants\RestaurantOrder;
use App\Models\Tenants\RestaurantMenuItem;
use Illuminate\Support\Facades\DB;
use App\Services\Common\Security\AIAnomalyDetector;
use Exception;

class RestaurantPOSManager
{
    protected AIAnomalyDetector $detector;

    public function __construct(AIAnomalyDetector $detector)
    {
        $this->detector = $detector;
    }

    /**
     * Создание заказа на стол (Table Service).
     */
    public function createOrder(int $tableId, int $waiterId, array $items): RestaurantOrder
    {
        $table = RestaurantTable::findOrFail($tableId);
        
        if ($table->status === 'occupied') {
            throw new Exception("Стол №{$table->table_number} уже занят.");
        }

        return DB::transaction(function () use ($table, $waiterId, $items) {
            $table->update(['status' => 'occupied']);

            $order = RestaurantOrder::create([
                'table_id' => $table->id,
                'waiter_id' => $waiterId,
                'total_amount' => 0,
                'status' => 'pending',
                'correlation_id' => (string) \Illuminate\Support\Str::uuid()
            ]);

            $total = 0;
            foreach ($items as $item) {
                $menuItem = RestaurantMenuItem::findOrFail($item['id']);
                $total += $menuItem->price * $item['quantity'];

                $order->items()->create([
                    'menu_item_id' => $menuItem->id,
                    'quantity' => $item['quantity'],
                    'price' => $menuItem->price,
                    'notes' => $item['notes'] ?? null
                ]);
            }

            $order->update(['total_amount' => $total]);

            return $order;
        });
    }

    /**
     * Закрытие счета (Checkout).
     */
    public function settleOrder(RestaurantOrder $order): void
    {
        DB::transaction(function () use ($order) {
            $order->update(['status' => 'paid']);
            $order->table->update(['status' => 'free']);
            
            // Финансовая интеграция с Wallet (комиссии/выплаты)
            // Кошелек Тенанта (ресторана) ++
        });
    }
}
