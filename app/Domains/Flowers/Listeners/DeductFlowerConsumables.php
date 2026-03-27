<?php

declare(strict_types=1);

namespace App\Domains\Flowers\Listeners;

use App\Domains\Flowers\Events\FlowerOrderCreated;
use App\Domains\Flowers\Models\FlowerConsumable;
use App\Domains\Flowers\Models\FlowerProduct;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * КАНОН 2026: DeductFlowerConsumables (Flowers).
 * Автоматическое списание расходных материалов (ленты, бумаги, губок) и цветов после заказа.
 */
final class DeductFlowerConsumables
{
    /**
     * Обработка создания заказа
     */
    public function handle(FlowerOrderCreated $event): void
    {
        $order = $event->order;
        $correlationId = $event->correlation_id;

        DB::transaction(function () use ($order, $correlationId) {
            // 1. Списание цветов (Inventory)
            $items = $order->items_json ?? [];
            foreach ($items as $item) {
                if (isset($item['product_id'], $item['quantity'])) {
                    $product = FlowerProduct::find($item['product_id']);
                    if ($product) {
                        $product->decrement('current_stock', (int)$item['quantity']);
                        Log::channel('audit')->info('Flower Inventory Deducted', [
                            'product_id' => $product->id,
                            'quantity' => $item['quantity'],
                            'order_id' => $order->id,
                            'correlation_id' => $correlationId,
                        ]);
                    }
                }
            }

            // 2. Списание расходных материалов (Consumables)
            // Допустим, каждый заказ списывает 1 шт "Упаковка" (id=1) 
            // Реальная логика: из метаданных букета
            $packaging = FlowerConsumable::where('name', 'LIKE', '%Упаковка%')->first();
            if ($packaging) {
                $packaging->decrement('current_stock', 1);
                Log::channel('audit')->info('Flower Consumable Deducted', [
                    'consumable_id' => $packaging->id,
                    'quantity' => 1,
                    'order_id' => $order->id,
                    'correlation_id' => $correlationId,
                ]);
            }
            
            // 3. Дополнительные расходники (ленты и т.д.)
            $ribbon = FlowerConsumable::where('name', 'LIKE', '%Лента%')->first();
            if ($ribbon) {
                $ribbon->decrement('current_stock', 3); // 3 метра ленты
                Log::channel('audit')->info('Flower Ribbon Consumable Deducted', [
                    'consumable_id' => $ribbon->id,
                    'quantity' => 3,
                    'correlation_id' => $correlationId,
                ]);
            }
        });
    }
}
