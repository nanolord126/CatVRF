<?php declare(strict_types=1);

namespace App\Domains\Food\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class KitchenDisplayService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraudControlService,
        ) {}

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

                $this->fraudControlService->check(
                    auth()->id() ?? 0,
                    __CLASS__ . '::' . __FUNCTION__,
                    0,
                    request()->ip(),
                    null,
                    $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
                );
    DB::transaction(function () use ($order, $correlationId) {
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
                $this->fraudControlService->check(
                    auth()->id() ?? 0,
                    __CLASS__ . '::' . __FUNCTION__,
                    0,
                    request()->ip(),
                    null,
                    $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
                );
    DB::transaction(function () use ($kdsOrder, $correlationId) {
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
