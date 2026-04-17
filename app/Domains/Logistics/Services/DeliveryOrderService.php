<?php declare(strict_types=1);

namespace App\Domains\Logistics\Services;

use App\Domains\Payment\Services\PaymentServiceAdapter;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;

final readonly class DeliveryOrderService
{
    public function __construct(private readonly FraudControlService $fraud,
            private readonly SurgePricingService $surgePricing,
            private readonly CourierService $courierService,
            private readonly PaymentServiceAdapter $payment,
            private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}

        /**
         * Инициация заказа на доставку (Production Ready)
         */
        public function createOrder(array $data, string $correlationId): DeliveryOrder
        {
            return $this->db->transaction(function () use ($data, $correlationId) {
                // 1. Rate Limiting (user-aware)
                if ($this->rateLimiter->tooManyAttempts("create_delivery_order:".($data['customer_id'] ?? 'guest'), 5)) {
                    throw new \RuntimeException("Слишком много попыток создания заказа. Подождите.");
                }
                $this->rateLimiter->hit("create_delivery_order:".($data['customer_id'] ?? 'guest'), 300);

                // 2. Fraud Check перед созданием
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'delivery_order_create', amount: 0, correlationId: $correlationId ?? '');

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

                $this->logger->info('Delivery order created', [
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
            $this->db->transaction(function () use ($order, $courier, $correlationId) {
                if (!$courier->is_available) {
                    throw new \RuntimeException("Курьер более не доступен для заказа.");
                }

                $order->assignCourier($courier->id, $correlationId);
                $courier->update(['is_available' => false]);

                $this->logger->info('Courier manual assignment', [
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
            $this->db->transaction(function () use ($order, $reason, $correlationId) {
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

                $this->logger->warning('Delivery order cancelled', [
                    'order_uuid' => $order->uuid,
                    'reason' => $reason,
                    'correlation_id' => $correlationId
                ]);
            });
        }
}
