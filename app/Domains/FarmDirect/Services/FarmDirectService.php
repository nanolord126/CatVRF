<?php declare(strict_types=1);

namespace App\Domains\FarmDirect\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FarmDirectService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraudControlService,
        ) {}

        /**
         * Создать заказ с фермы.
         */
        public function createOrder(
            int    $clientId,
            int    $farmId,
            array  $items,
            string $deliveryAddress,
            string $deliveryDate,
            int    $tenantId,
        ): FarmOrder {


            $correlationId = Str::uuid()->toString();
            $key           = "farm_order:{$tenantId}:{$clientId}";

            if (RateLimiter::tooManyAttempts($key, 10)) {
                Log::channel('audit')->warning('FarmDirect: rate limit exceeded', [
                    'correlation_id' => $correlationId,
                    'client_id'      => $clientId,
                    'tenant_id'      => $tenantId,
                ]);
                throw new RuntimeException('Превышен лимит заказов. Попробуйте позже.');
            }
            RateLimiter::hit($key, 3600);

            $totalAmount = array_sum(array_column($items, 'amount'));

            $fraudResult = $this->fraudControlService->check(
                userId:        $clientId,
                operationType: 'farm_order_create',
                amount:        $totalAmount,
                correlationId: $correlationId,
            );

            if ($fraudResult['decision'] === 'block') {
                Log::channel('audit')->warning('FarmDirect: fraud block', [
                    'correlation_id' => $correlationId,
                    'ml_score'       => $fraudResult['score'],
                    'client_id'      => $clientId,
                ]);
                throw new RuntimeException('Операция заблокирована системой безопасности.');
            }

            $idempotencyKey = md5("{$clientId}:{$farmId}:{$deliveryDate}:" . json_encode($items));

            if (FarmOrder::where('idempotency_key', $idempotencyKey)->exists()) {
                /** @var FarmOrder $existing */
                $existing = FarmOrder::where('idempotency_key', $idempotencyKey)->firstOrFail();
                Log::channel('audit')->info('FarmDirect: duplicate order (idempotency)', [
                    'correlation_id' => $correlationId,
                    'order_id'       => $existing->id,
                ]);
                return $existing;
            }

            return DB::transaction(function () use (
                $clientId, $farmId, $items, $deliveryAddress,
                $deliveryDate, $tenantId, $correlationId, $idempotencyKey, $totalAmount
            ) {
                $order = FarmOrder::create([
                    'tenant_id'        => $tenantId,
                    'client_id'        => $clientId,
                    'farm_id'          => $farmId,
                    'correlation_id'   => $correlationId,
                    'idempotency_key'  => $idempotencyKey,
                    'items'            => $items,
                    'total_amount'     => $totalAmount,
                    'delivery_address' => $deliveryAddress,
                    'delivery_date'    => $deliveryDate,
                    'status'           => 'pending',
                    'payment_status'   => 'awaiting',
                ]);

                event(new FarmOrderCreated($order, $correlationId));

                Log::channel('audit')->info('FarmDirect: order created', [
                    'correlation_id' => $correlationId,
                    'order_id'       => $order->id,
                    'total_amount'   => $totalAmount,
                    'tenant_id'      => $tenantId,
                ]);

                return $order;
            });
        }

        /**
         * Отправить заказ (статус → shipped).
         */
        public function markShipped(int $orderId): FarmOrder
        {


            $correlationId = Str::uuid()->toString();

            return DB::transaction(function () use ($orderId, $correlationId) {
                /** @var FarmOrder $order */
                $order = FarmOrder::lockForUpdate()->findOrFail($orderId);

                if (!in_array($order->status, ['pending', 'confirmed'], true)) {
                    throw new RuntimeException("Нельзя отправить заказ со статусом {$order->status}.");
                }

                $order->update(['status' => 'shipped', 'shipped_at' => now()]);

                event(new FarmOrderShipped($order, $correlationId));

                Log::channel('audit')->info('FarmDirect: order shipped', [
                    'correlation_id' => $correlationId,
                    'order_id'       => $order->id,
                ]);

                return $order;
            });
        }

        /**
         * Отметить заказ доставленным.
         */
        public function markDelivered(int $orderId): FarmOrder
        {


            $correlationId = Str::uuid()->toString();

            return DB::transaction(function () use ($orderId, $correlationId) {
                /** @var FarmOrder $order */
                $order = FarmOrder::lockForUpdate()->findOrFail($orderId);

                $order->update(['status' => 'delivered', 'delivered_at' => now()]);

                Log::channel('audit')->info('FarmDirect: order delivered', [
                    'correlation_id' => $correlationId,
                    'order_id'       => $order->id,
                ]);

                return $order;
            });
        }

        /**
         * Список продуктов фермы с фильтром сезонности.
         */
        public function getProductsBySeason(int $farmId, int $month): \Illuminate\Database\Eloquent\Collection
        {


            return FarmProduct::where('farm_id', $farmId)
                ->where('status', 'active')
                ->where(function ($q) use ($month) {
                    $q->where('is_seasonal', false)
                      ->orWhereJsonContains('season_months', $month);
                })
                ->orderBy('name')
                ->get();
        }

        /**
         * Проверенные эко-фермы тенанта.
         */
        public function getVerifiedFarms(int $tenantId): \Illuminate\Database\Eloquent\Collection
        {


            return Farm::where('tenant_id', $tenantId)
                ->where('is_verified', true)
                ->where('status', 'active')
                ->orderByDesc('rating')
                ->get();
        }
}
