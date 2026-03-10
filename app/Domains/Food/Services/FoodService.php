<?php

namespace App\Domains\Food\Services;

use App\Domains\Food\Models\{RestaurantOrder, RestaurantOrderItem, RestaurantMenuItem};
use App\Domains\Finances\Services\PaymentService;
use App\Domains\Finances\Services\WalletService;
use App\Models\AuditLog;
use Illuminate\Support\Facades\{DB, Log, Auth};
use Illuminate\Support\Str;
use Throwable;
use Exception;

/**
 * Сервис управления ресторанными заказами.
 * Обеспечивает создание, оплату, подготовку и завершение заказов.
 */
class FoodService
{
    public function __construct(
        private PaymentService $paymentService,
        private WalletService $walletService
    ) {}

    /**
     * Создать новый заказ в ресторане.
     */
    public function createOrder(array $data): RestaurantOrder
    {
        DB::beginTransaction();
        try {
            // Валидация данных
            if (empty($data['table_id']) || empty($data['items'])) {
                throw new Exception('Table ID и Items обязательны');
            }

            if ($data['total_amount'] <= 0) {
                throw new Exception('Сумма заказа должна быть больше 0');
            }

            $correlationId = $data['correlation_id'] ?? Str::uuid()->toString();

            // Создать основной заказ
            $order = RestaurantOrder::create([
                'table_id' => $data['table_id'],
                'waiter_id' => Auth::id() ?? $data['waiter_id'],
                'total_amount' => $data['total_amount'],
                'status' => RestaurantOrder::STATUS_PENDING,
                'correlation_id' => $correlationId,
                'is_tax_inclusive' => $data['is_tax_inclusive'] ?? true,
                'tenant_id' => Auth::guard('tenant')->id(),
            ]);

            // Добавить позиции заказа
            $items = [];
            foreach ($data['items'] as $item) {
                $menuItem = RestaurantMenuItem::findOrFail($item['menu_item_id']);
                
                $items[] = [
                    'order_id' => $order->id,
                    'menu_item_id' => $item['menu_item_id'],
                    'quantity' => $item['quantity'],
                    'price' => $menuItem->price,
                    'notes' => $item['notes'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            RestaurantOrderItem::insert($items);

            // Создать запись в audit log
            AuditLog::create([
                'entity_type' => RestaurantOrder::class,
                'entity_id' => $order->id,
                'action' => 'created',
                'user_id' => Auth::id(),
                'tenant_id' => Auth::guard('tenant')->id(),
                'correlation_id' => $correlationId,
                'changes' => $order->getAttributes(),
            ]);

            Log::channel('food')->info('RestaurantOrder created successfully', [
                'order_id' => $order->id,
                'table_id' => $data['table_id'],
                'total_amount' => $data['total_amount'],
                'items_count' => count($data['items']),
                'correlation_id' => $correlationId,
            ]);

            DB::commit();
            return $order;
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Failed to create RestaurantOrder', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Оплатить заказ через указанный метод.
     */
    public function payOrder(RestaurantOrder $order, string $paymentMethod = 'card', array $paymentData = []): array
    {
        DB::beginTransaction();
        try {
            if ($order->status !== RestaurantOrder::STATUS_PENDING) {
                throw new Exception("Заказ уже обработан (статус: {$order->status})");
            }

            $correlationId = $paymentData['correlation_id'] ?? Str::uuid()->toString();

            if ($paymentMethod === 'wallet') {
                // Оплата с кошелька пользователя
                $user = $order->waiter;
                if (!$user) {
                    throw new Exception('Пользователь не найден');
                }

                $result = $this->walletService->debit(
                    $user,
                    $order->total_amount,
                    "Restaurant Order #{$order->id}",
                    $order->id,
                    $correlationId
                );

                if (!$result) {
                    throw new Exception('Недостаточно средств в кошельке');
                }

                $order->update([
                    'status' => RestaurantOrder::STATUS_PAID,
                    'paid_at' => now(),
                    'payment_method' => 'wallet',
                ]);

                Log::channel('food')->info('Order paid from wallet', [
                    'order_id' => $order->id,
                    'amount' => $order->total_amount,
                    'user_id' => $user->id,
                    'correlation_id' => $correlationId,
                ]);

                DB::commit();
                return [
                    'success' => true,
                    'payment_method' => 'wallet',
                    'order_id' => $order->id,
                    'correlation_id' => $correlationId,
                ];
            } elseif ($paymentMethod === 'card') {
                // Инициировать платёж через платёжный шлюз
                $paymentResult = $this->paymentService->initPayment([
                    'amount' => $order->total_amount,
                    'order_id' => "ORD-{$order->id}",
                    'user_id' => $order->waiter_id,
                    'order_type' => 'restaurant_order',
                    'description' => "Restaurant Order #{$order->id}",
                    'correlation_id' => $correlationId,
                    'metadata' => [
                        'table_id' => $order->table_id,
                        'items_count' => $order->items()->count(),
                    ],
                ]);

                $order->update([
                    'status' => RestaurantOrder::STATUS_PAYMENT_PENDING,
                    'payment_method' => 'card',
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('food')->info('Order payment initiated', [
                    'order_id' => $order->id,
                    'payment_id' => $paymentResult['payment_id'] ?? null,
                    'correlation_id' => $correlationId,
                ]);

                DB::commit();
                return [
                    'success' => true,
                    'payment_method' => 'card',
                    'payment_url' => $paymentResult['payment_url'] ?? null,
                    'order_id' => $order->id,
                    'correlation_id' => $correlationId,
                ];
            } else {
                throw new Exception("Неподдерживаемый метод оплаты: {$paymentMethod}");
            }
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Failed to pay order', [
                'order_id' => $order->id,
                'payment_method' => $paymentMethod,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Отметить заказ как готовый к подаче.
     */
    public function markOrderReady(RestaurantOrder $order): RestaurantOrder
    {
        try {
            if ($order->status !== RestaurantOrder::STATUS_PAID) {
                throw new Exception("Заказ должен быть оплачен перед подготовкой (текущий статус: {$order->status})");
            }

            $order->update([
                'status' => RestaurantOrder::STATUS_READY,
                'prepared_at' => now(),
            ]);

            Log::channel('food')->info('Order marked as ready', [
                'order_id' => $order->id,
                'table_id' => $order->table_id,
                'prepared_at' => $order->prepared_at,
            ]);

            return $order;
        } catch (Throwable $e) {
            Log::error('Failed to mark order as ready', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Завершить заказ (подано и готово к завершению).
     */
    public function completeOrder(RestaurantOrder $order): RestaurantOrder
    {
        try {
            if ($order->status !== RestaurantOrder::STATUS_READY) {
                throw new Exception("Заказ должен быть готов перед завершением (текущий статус: {$order->status})");
            }

            $order->update([
                'status' => RestaurantOrder::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            // Создать аудит запись
            AuditLog::create([
                'entity_type' => RestaurantOrder::class,
                'entity_id' => $order->id,
                'action' => 'completed',
                'user_id' => Auth::id(),
                'tenant_id' => $order->tenant_id,
                'correlation_id' => $order->correlation_id,
                'changes' => ['status' => RestaurantOrder::STATUS_COMPLETED, 'completed_at' => $order->completed_at],
            ]);

            Log::channel('food')->info('Order completed', [
                'order_id' => $order->id,
                'table_id' => $order->table_id,
                'completed_at' => $order->completed_at,
            ]);

            return $order;
        } catch (Throwable $e) {
            Log::error('Failed to complete order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Отменить заказ с возвратом средств.
     */
    public function cancelOrder(RestaurantOrder $order, string $reason = null): RestaurantOrder
    {
        DB::beginTransaction();
        try {
            if (in_array($order->status, [RestaurantOrder::STATUS_COMPLETED, RestaurantOrder::STATUS_CANCELLED])) {
                throw new Exception("Невозможно отменить заказ со статусом: {$order->status}");
            }

            // Если заказ уже оплачен - вернуть средства
            if ($order->status === RestaurantOrder::STATUS_PAID && $order->payment_method === 'wallet') {
                $user = $order->waiter;
                $this->walletService->credit(
                    $user,
                    $order->total_amount,
                    "Возврат за отменённый заказ #{$order->id}",
                    $order->id
                );
            }

            $order->update([
                'status' => RestaurantOrder::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            // Создать аудит запись
            AuditLog::create([
                'entity_type' => RestaurantOrder::class,
                'entity_id' => $order->id,
                'action' => 'cancelled',
                'user_id' => Auth::id(),
                'tenant_id' => $order->tenant_id,
                'correlation_id' => $order->correlation_id,
                'changes' => [
                    'status' => RestaurantOrder::STATUS_CANCELLED,
                    'cancelled_at' => $order->cancelled_at,
                    'cancellation_reason' => $reason,
                ],
            ]);

            Log::channel('food')->info('Order cancelled', [
                'order_id' => $order->id,
                'reason' => $reason,
                'refunded_amount' => $order->payment_method === 'wallet' ? $order->total_amount : 0,
            ]);

            DB::commit();
            return $order;
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Failed to cancel order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Получить статистику по заказам ресторана.
     */
    public function getRestaurantStats(int $tenantId, array $filters = []): array
    {
        try {
            $query = RestaurantOrder::where('tenant_id', $tenantId);

            // Фильтр по дате
            if (!empty($filters['date_from'])) {
                $query->where('created_at', '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $query->where('created_at', '<=', $filters['date_to']);
            }

            // Фильтр по статусу
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            $orders = $query->get();

            return [
                'total_orders' => $orders->count(),
                'total_revenue' => (float) $orders->sum('total_amount'),
                'completed_orders' => $orders->where('status', 'completed')->count(),
                'cancelled_orders' => $orders->where('status', 'cancelled')->count(),
                'average_order_value' => $orders->count() > 0 ? (float) $orders->avg('total_amount') : 0,
                'payment_methods' => $orders->groupBy('payment_method')->map->count()->toArray(),
            ];
        } catch (Throwable $e) {
            Log::error('Failed to get restaurant stats', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
