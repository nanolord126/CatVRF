<?php

namespace Modules\Inventory\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Str;

class PredictInventoryNeedsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Предсказание дефицита и авто-генерация запроса на закупку (Draft).
     */
    public function handle()
    {
        // Канон: Проверка по всем расходникам
        Product::where('category', 'CONSUMABLE')->each(function (Product $product) {
            $correlationId = (string) Str::uuid();

            // Упрощенная логика: средний расход за последнюю неделю против текущего остатка
            $usageLastWeek = StockMovement::where('product_id', $product->id)
                ->where('type', 'OUT')
                ->where('created_at', '>=', now()->subDays(7))
                ->sum('quantity');

            $dailyAvg = $usageLastWeek / 7;
            $predictedDaysLeft = ($dailyAvg > 0) ? ($product->stock_quantity / $dailyAvg) : 999;

            // Если запаса меньше чем на 3 дня - создаем черновик заявки
            if ($predictedDaysLeft < 3 && $product->stock_quantity < $product->min_stock_level) {
                // Создаем запрос на закупку/движение (StockMovement с типом IN, но в статусе 'draft')
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'IN',
                    'quantity' => round($dailyAvg * 7), // Предлагаем объем на неделю
                    'status' => 'draft',
                    'reason' => "AI PREDICTION: Stock exhaustion in " . round($predictedDaysLeft, 1) . " days. Daily usage: " . round($dailyAvg, 1),
                    'correlation_id' => $correlationId,
                    'tenant_id' => $product->tenant_id,
                ]);

                // Логируем событие в аудит
                \App\Models\StaffAuditLog::create([
                    'user_id' => 0, // System
                    'action' => 'AI_INVENTORY_FORECAST',
                    'resource_id' => $product->id,
                    'resource_type' => Product::class,
                    'description' => "System predicted critical stock levels for {$product->name}. Suggested reorder created as draft.",
                    'correlation_id' => $correlationId,
                ]);
            }
        });
    }
}
