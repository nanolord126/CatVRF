<?php declare(strict_types=1);

namespace App\Domains\Food\Services;

use App\Domains\Food\Models\DeliveryOrder;
use App\Domains\Food\Models\DeliveryZone;
use App\Domains\Food\Models\RestaurantOrder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Сервис для управления доставкой и surge pricing.
 * Production 2026.
 */
final class DeliveryService
{
    /**
     * Рассчитать стоимость доставки с учётом surge.
     */
    public function calculateDeliveryPrice(
        RestaurantOrder $order,
        array $deliveryPoint,
        string $correlationId = ''
    ): int {
        try {
            $zone = DeliveryZone::query()
                ->where('restaurant_id', $order->restaurant_id)
                ->first();

            if (!$zone) {
                // Default delivery price
                $basePrice = 50000; // 500 руб
            } else {
                $basePrice = $zone->base_delivery_price;
            }

            $surgeMultiplier = $zone?->surge_multiplier ?? 1.0;
            $finalPrice = (int) ($basePrice * $surgeMultiplier);

            Log::channel('audit')->info('Delivery price calculated', [
                'order_id' => $order->id,
                'base_price' => $basePrice,
                'surge' => $surgeMultiplier,
                'final_price' => $finalPrice,
                'correlation_id' => $correlationId,
            ]);

            return $finalPrice;
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Delivery price calculation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }

    /**
     * Создать заказ доставки.
     */
    public function createDeliveryOrder(
        RestaurantOrder $order,
        string $customerAddress,
        array $deliveryPoint,
        string $correlationId = ''
    ): DeliveryOrder {
        try {
            return DB::transaction(function () use ($order, $customerAddress, $deliveryPoint, $correlationId) {
                $deliveryPrice = $this->calculateDeliveryPrice($order, $deliveryPoint, $correlationId);

                $delivery = DeliveryOrder::create([
                    'tenant_id' => $order->tenant_id,
                    'restaurant_order_id' => $order->id,
                    'customer_address' => $customerAddress,
                    'delivery_point' => $deliveryPoint,
                    'delivery_price' => $deliveryPrice,
                    'status' => 'pending',
                    'surge_multiplier' => 1.0,
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Delivery order created', [
                    'delivery_id' => $delivery->id,
                    'order_id' => $order->id,
                    'price' => $deliveryPrice,
                    'correlation_id' => $correlationId,
                ]);

                return $delivery;
            });
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Delivery order creation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }

    /**
     * Начать доставку.
     */
    public function startDelivery(DeliveryOrder $delivery, string $correlationId = ''): bool
    {
        try {
            return DB::transaction(function () use ($delivery, $correlationId) {
                $delivery->update([
                    'status' => 'on_way',
                    'picked_up_at' => now(),
                ]);

                event(new \App\Domains\Food\Events\DeliveryStarted($delivery, $correlationId));

                Log::channel('audit')->info('Delivery started', [
                    'delivery_id' => $delivery->id,
                    'correlation_id' => $correlationId,
                ]);

                return true;
            });
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Delivery start failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }
}
