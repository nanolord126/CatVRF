<?php declare(strict_types=1);

namespace App\Domains\Food\Services;

use App\Domains\Food\Models\KDSOrder;
use App\Domains\Food\Models\RestaurantOrder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Сервис для управления KDS (Kitchen Display System).
 * Production 2026.
 */
final class KitchenDisplayService
{
    /**
     * Создать KDS-заказ при оплате.
     */
    public function createKDSOrder(
        RestaurantOrder $order,
        string $correlationId = ''
    ): KDSOrder {
        try {
            Log::channel('audit')->info('Creating KDS order', [
                'order_id' => $order->id,
                'correlation_id' => $correlationId,
            ]);

            return DB::transaction(function () use ($order, $correlationId) {
                $kdsOrder = KDSOrder::create([
                    'tenant_id' => $order->tenant_id,
                    'restaurant_order_id' => $order->id,
                    'items_json' => $order->items_json,
                    'status' => 'new',
                    'total_cooking_time_minutes' => $this->calculateCookingTime($order),
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('KDS order created', [
                    'kds_id' => $kdsOrder->id,
                    'correlation_id' => $correlationId,
                ]);

                return $kdsOrder;
            });
        } catch (\Throwable $e) {
            Log::channel('audit')->error('KDS order creation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }

    /**
     * Пересчитать общее время готовки для заказа.
     */
    public function calculateCookingTime(RestaurantOrder $order): int
    {
        $maxTime = 0;

        foreach ($order->items_json ?? [] as $item) {
            $dish = \App\Domains\Food\Models\Dish::find($item['dish_id'] ?? null);
            if ($dish && $dish->cooking_time_minutes > $maxTime) {
                $maxTime = $dish->cooking_time_minutes;
            }
        }

        return $maxTime > 0 ? $maxTime : 15;
    }

    /**
     * Отметить заказ в KDS как готовый.
     */
    public function markAsReady(KDSOrder $kdsOrder, string $correlationId = ''): bool
    {
        try {
            return DB::transaction(function () use ($kdsOrder, $correlationId) {
                $kdsOrder->update([
                    'status' => 'ready',
                    'ready_at' => now(),
                ]);

                Log::channel('audit')->info('KDS order marked as ready', [
                    'kds_id' => $kdsOrder->id,
                    'correlation_id' => $correlationId,
                ]);

                return true;
            });
        } catch (\Throwable $e) {
            Log::channel('audit')->error('KDS mark ready failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }
}
