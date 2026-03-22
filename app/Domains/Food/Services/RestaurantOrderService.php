<?php declare(strict_types=1);

namespace App\Domains\Food\Services;

use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

use App\Domains\Food\Models\RestaurantOrder;
use Illuminate\Support\Facades\DB;

/**
 * Сервис для управления заказами в ресторане.
 * Production 2026.
 */
final class RestaurantOrderService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
        private readonly KitchenDisplayService $kitchenService,
        private readonly DeliveryService $deliveryService,
    ) {}

    /**
     * Создать заказ в ресторане.
     */
    public function createOrder(
        array $data,
        string $correlationId = ''
    ): RestaurantOrder {


        try {
            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
DB::transaction(function () use ($data, $correlationId) {
                $order = RestaurantOrder::create([
                    'tenant_id' => $data['tenant_id'],
                    'restaurant_id' => $data['restaurant_id'],
                    'table_id' => $data['table_id'] ?? null,
                    'client_id' => $data['client_id'] ?? null,
                    'order_number' => 'ORD-' . now()->timestamp,
                    'status' => 'pending',
                    'items_json' => $data['items'] ?? [],
                    'subtotal_price' => $data['subtotal_price'] ?? 0,
                    'delivery_price' => $data['delivery_price'] ?? 0,
                    'commission_price' => (int) (($data['subtotal_price'] ?? 0) * 0.14), // 14% комиссия
                    'total_price' => $data['total_price'] ?? 0,
                    'payment_status' => 'pending',
                    'customer_notes' => $data['notes'] ?? null,
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Restaurant order created', [
                    'order_id' => $order->id,
                    'restaurant_id' => $order->restaurant_id,
                    'total_price' => $order->total_price,
                    'correlation_id' => $correlationId,
                ]);

                return $order;
            });
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Restaurant order creation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }

    /**
     * Подтвердить оплату и отправить на кухню.
     */
    public function confirmPaymentAndSendToKitchen(
        RestaurantOrder $order,
        string $correlationId = ''
    ): bool {


        try {
            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
DB::transaction(function () use ($order, $correlationId) {
                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'confirmed',
                ]);

                // Создать KDS-заказ
                $this->kitchenService->createKDSOrder($order, $correlationId);

                Log::channel('audit')->info('Order sent to kitchen', [
                    'order_id' => $order->id,
                    'correlation_id' => $correlationId,
                ]);

                return true;
            });
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Payment confirmation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }

    /**
     * Завершить заказ.
     */
    public function completeOrder(
        RestaurantOrder $order,
        string $correlationId = ''
    ): bool {


        try {
            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
DB::transaction(function () use ($order, $correlationId) {
                $order->update([
                    'status' => 'delivered',
                    'completed_at' => now(),
                ]);

                event(new \App\Domains\Food\Events\OrderCompleted($order, $correlationId));

                Log::channel('audit')->info('Order completed', [
                    'order_id' => $order->id,
                    'correlation_id' => $correlationId,
                ]);

                return true;
            });
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Order completion failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }
}
