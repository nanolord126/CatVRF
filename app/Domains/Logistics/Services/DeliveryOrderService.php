<?php declare(strict_types=1);

namespace App\Domains\Logistics\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DeliveryOrderService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraud,
            private readonly SurgePricingService $surgePricing,
            private readonly CourierService $courierService,
            private readonly PaymentService $payment,
            private readonly WalletService $wallet,
        ) {}

        /**
         * Инициация заказа на доставку (Production Ready)
         */
        public function createOrder(array $data, string $correlationId): DeliveryOrder
        {
            return DB::transaction(function () use ($data, $correlationId) {
                // 1. Rate Limiting (user-aware)
                if (RateLimiter::tooManyAttempts("create_delivery_order:".($data['customer_id'] ?? 'guest'), 5)) {
                    throw new \RuntimeException("Слишком много попыток создания заказа. Подождите.");
                }
                RateLimiter::hit("create_delivery_order:".($data['customer_id'] ?? 'guest'), 300);

                // 2. Fraud Check перед созданием
                $this->fraud->check([
                    'operation_type' => 'delivery_order_create',
                    'correlation_id' => $correlationId,
                    'payload' => $data
                ]);

                // 3. Расчет коэффициента Surge
                $pickup = $data['pickup_point'];
                $surge = $this->surgePricing->calculateSurge($pickup['lat'], $pickup['lon'], $data['vertical'] ?? 'general');

                // 4. Создание заказа
                $order = DeliveryOrder::create([
                    'uuid' => (string) Str::uuid(),
                    'customer_id' => $data['customer_id'] ?? null,
                    'business_group_id' => $data['business_group_id'] ?? null,
                    'pickup_point' => $pickup,
                    'dropoff_point' => $data['dropoff_point'],
                    'pickup_address' => $data['pickup_address'] ?? [],
                    'dropoff_address' => $data['dropoff_address'] ?? [],
                    'status' => 'pending',
                    'base_price' => $data['base_price'] ?? 10000, // В копейках
                    'surge_multiplier' => $surge['multiplier'] ?? 1.0,
                    'estimated_delivery_at' => now()->addMinutes(45),
                    'correlation_id' => $correlationId,
                    'tags' => ['surge_applied' => $surge['multiplier'] > 1.0]
                ]);

                Log::channel('audit')->info('Delivery order created', [
                    'order_uuid' => $order->uuid,
                    'multiplier' => $order->surge_multiplier,
                    'correlation_id' => $correlationId
                ]);

                return $order;
            });
        }

        /**
         * Назначение курьера на заказ
         */
        public function assignCourier(DeliveryOrder $order, Courier $courier, string $correlationId): void
        {
            DB::transaction(function () use ($order, $courier, $correlationId) {
                if (!$courier->is_available) {
                    throw new \RuntimeException("Курьер более не доступен для заказа.");
                }

                $order->assignCourier($courier->id, $correlationId);
                $courier->update(['is_available' => false]);

                Log::channel('audit')->info('Courier manual assignment', [
                    'order_id' => $order->id,
                    'courier_id' => $courier->id,
                    'correlation_id' => $correlationId
                ]);
            });
        }

        /**
         * Отмена заказа
         */
        public function cancelOrder(DeliveryOrder $order, string $reason, string $correlationId): void
        {
            DB::transaction(function () use ($order, $reason, $correlationId) {
                if ($order->isCompleted()) {
                    throw new \RuntimeException("Нельзя отменить уже завершенный заказ.");
                }

                $order->update([
                    'status' => 'cancelled',
                    'metadata' => array_merge($order->metadata ?? [], [
                        'cancel_reason' => $reason,
                        'cancelled_at' => now()->toIso8601String()
                    ]),
                    'correlation_id' => $correlationId
                ]);

                // Если был курьер — освобождаем его
                if ($order->courier_id) {
                    Courier::where('id', $order->courier_id)->update(['is_available' => true]);
                }

                Log::channel('audit')->warning('Delivery order cancelled', [
                    'order_uuid' => $order->uuid,
                    'reason' => $reason,
                    'correlation_id' => $correlationId
                ]);
            });
        }
}
