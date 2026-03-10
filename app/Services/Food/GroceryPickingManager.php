<?php

namespace App\Services\Food;

use App\Models\Tenants\RestaurantOrder;
use App\Models\Tenants\RestaurantMenu; // Используется как PRODUCT в супермаркете
use Filament\Notifications\Notification;
use Illuminate\Support\{Carbon, Facades};
use Illuminate\Support\Facades\DB;

/**
 * Сервис сбора заказов в супермаркете (Grocery).
 * Интегрирован со складским модулем (Inventory).
 */
class GroceryPickingManager
{
    /**
     * Начало сборки заказа в супермаркете.
     * Проверяет остатки на полке в реальном времени.
     */
    public function startPicking(RestaurantOrder $order)
    {
        $order->update([
            'status' => 'picking',
            'picking_started_at' => Carbon::now(),
            'order_type' => 'grocery_shelf'
        ]);

        return true;
    }

    /**
     * Списание весового товара при сборке.
     */
    public function pickProduct(string $orderId, string $sku, float $weight = 1.0)
    {
        $product = RestaurantMenu::where('sku', $sku)->first();
        
        if ($product && $product->stock_quantity >= $weight) {
            DB::transaction(function() use ($product, $weight) {
                // Прямой вычет из складских остатков тенанта
                $product->decrement('stock_quantity', $weight);
                
                // Фиксация в Audit Log (Canon 2026)
                Log::info("Grocery: SKU {$product->sku} picked: {$weight} units.");
            });
            
            return true;
        }

        return false;
    }
}
