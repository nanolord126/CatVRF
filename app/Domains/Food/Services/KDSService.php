<?php declare(strict_types=1);

namespace App\Domains\Food\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class KDSService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Send order to KDS
         *
         * @param int $orderId Order ID
         * @param array $items Order items {dish_id, quantity, notes}
         * @param string $correlationId Tracing ID
         * @return array {order_id, status, sent_at}
         * @throws \Exception
         */
        public function sendToKitchen(int $orderId, array $items, string $correlationId): array
        {
            return DB::transaction(function () use ($orderId, $items, $correlationId): array {
                // Get order
                $order = DB::table('restaurant_orders')->findOrFail($orderId);

                // Update order status
                DB::table('restaurant_orders')
                    ->where('id', $orderId)
                    ->update([
                        'status' => 'cooking',
                        'cooking_started_at' => now(),
                        'updated_at' => now(),
                    ]);

                // Send to KDS (stub - would call actual KDS API)
                $this->callKDSApi([
                    'order_id' => $orderId,
                    'restaurant_id' => $order->restaurant_id,
                    'items' => $items,
                    'notes' => $order->special_instructions ?? '',
                    'priority' => $this->calculatePriority($order->total_price),
                ]);

                Log::channel('audit')->info('Order sent to KDS', [
                    'correlation_id' => $correlationId,
                    'order_id' => $orderId,
                    'restaurant_id' => $order->restaurant_id,
                    'item_count' => count($items),
                ]);

                return [
                    'order_id' => $orderId,
                    'status' => 'cooking',
                    'sent_at' => now(),
                ];
            });
        }

        /**
         * Mark order as ready in KDS
         *
         * @param int $orderId Order ID
         * @param string $correlationId Tracing ID
         * @return array {order_id, status, ready_at}
         */
        public function markOrderReady(int $orderId, string $correlationId): array
        {
            return DB::transaction(function () use ($orderId, $correlationId): array {
                // Get order
                $order = DB::table('restaurant_orders')->findOrFail($orderId);

                // Update status
                DB::table('restaurant_orders')
                    ->where('id', $orderId)
                    ->update([
                        'status' => 'ready',
                        'ready_at' => now(),
                        'updated_at' => now(),
                    ]);

                // Send to KDS
                $this->callKDSApi([
                    'action' => 'ready',
                    'order_id' => $orderId,
                    'restaurant_id' => $order->restaurant_id,
                ]);

                Log::channel('audit')->info('Order marked as ready', [
                    'correlation_id' => $correlationId,
                    'order_id' => $orderId,
                    'cooking_time_minutes' => now()->diffInMinutes($order->cooking_started_at),
                ]);

                return [
                    'order_id' => $orderId,
                    'status' => 'ready',
                    'ready_at' => now(),
                ];
            });
        }

        /**
         * Cancel order in KDS
         *
         * @param int $orderId Order ID
         * @param string $reason Cancellation reason
         * @param string $correlationId Tracing ID
         * @return bool
         */
        public function cancelOrder(int $orderId, string $reason, string $correlationId): bool
        {
            return DB::transaction(function () use ($orderId, $reason, $correlationId): bool {
                // Get order
                $order = DB::table('restaurant_orders')->findOrFail($orderId);

                // Update status
                DB::table('restaurant_orders')
                    ->where('id', $orderId)
                    ->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                        'cancellation_reason' => $reason,
                        'updated_at' => now(),
                    ]);

                // Send to KDS
                $this->callKDSApi([
                    'action' => 'cancel',
                    'order_id' => $orderId,
                    'restaurant_id' => $order->restaurant_id,
                    'reason' => $reason,
                ]);

                Log::channel('audit')->info('Order cancelled in KDS', [
                    'correlation_id' => $correlationId,
                    'order_id' => $orderId,
                    'reason' => $reason,
                ]);

                return true;
            });
        }

        /**
         * Get kitchen queue
         *
         * @param int $restaurantId Restaurant ID
         * @return array List of orders in kitchen
         */
        public function getKitchenQueue(int $restaurantId): array
        {
            return DB::table('restaurant_orders')
                ->where('restaurant_id', $restaurantId)
                ->whereIn('status', ['cooking', 'ready'])
                ->orderBy('cooking_started_at', 'asc')
                ->get(['id', 'status', 'total_items', 'cooking_started_at', 'priority'])
                ->toArray();
        }

        /**
         * Calculate order priority (based on price and preparation time)
         *
         * @param int $totalPrice Order total price
         * @return int Priority (1=high, 2=normal, 3=low)
         */
        private function calculatePriority(int $totalPrice): int
        {
            return match (true) {
                $totalPrice >= 50000 => 1, // High priority for expensive orders
                $totalPrice >= 20000 => 2, // Normal priority
                default => 3,              // Low priority
            };
        }

        /**
         * Call KDS API
         *
         * @param array $payload KDS payload
         * @return void
         */
        private function callKDSApi(array $payload): void
        {
            try {
                // For now, this is a stub
                Log::channel('audit')->debug('KDS API call', $payload);
            } catch (\Exception $e) {
                Log::channel('audit')->error('KDS API failed', [
                    'error' => $e->getMessage(),
                    'payload' => $payload,
                ]);
            }
        }
}
