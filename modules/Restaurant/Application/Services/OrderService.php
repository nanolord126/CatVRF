<?php

declare(strict_types=1);

namespace Modules\Restaurant\Services;

use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\CacheManager;
use Illuminate\Log\LogManager;
use Modules\Restaurant\Models\Order;
use Modules\Restaurant\Models\OrderItem;
use Modules\Restaurant\Models\Guest;
use Modules\Restaurant\Models\Table;
use Modules\Restaurant\Enums\OrderType;
use Modules\Restaurant\Enums\OrderStatus;
use Modules\Restaurant\Events\OrderCreated;
use Modules\Restaurant\Events\OrderStatusChanged;
use App\Services\Fraud\FraudControlService;
use App\Domains\CRM\Services\FoodCrmService;
use App\Domains\Shared\Geo\GeoLogisticsAdapter;
use App\Domains\Shared\Realtime\RealtimeTrackingAdapter;
use App\Domain\Audit\Events\AuditEvent;
use App\Traits\WithTelemetry;
use Illuminate\Support\Facades\Event;
use Modules\Analytics\Services\BehavioralTracker;

/**
 * Order Service — Сервис для управления заказами в ресторане
 *
 * CatVRF 2026 Canon - Production Mandatory
 * - Fraud check перед всеми мутациями
 * - Readonly класс
 * - Cache::tags для инвалидации
 * - Audit логирование
 * - CRM интеграция
 */
final readonly class OrderService
{
    use WithTelemetry;

    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly FoodCrmService $foodCrm,
        private readonly GeoLogisticsAdapter $geoAdapter,
        private readonly RealtimeTrackingAdapter $realtimeAdapter,
        private readonly DatabaseManager $db,
        private readonly CacheManager $cache,
        private readonly LogManager $logger,
        private readonly BehavioralTracker $behavioralTracker,
        private readonly string $correlationId
    ) {}

    /**
     * Создать новый заказ
     */
    public function createOrder(array $data): Order
    {
        return $this->withSpan('restaurant.create_order', function () use ($data) {
            // FRAUD CHECK - мандаторно первым действием
            $fraudResult = $this->fraud->checkRequest([
                'action' => 'restaurant_create_order',
                'user_id' => $data['user_id'] ?? null,
                'tenant_id' => $data['tenant_id'] ?? null,
                'ip_address' => request()->ip(),
            ]);

            if ($fraudResult['should_block']) {
                Event::dispatch(AuditEvent::action(
                    action: 'restaurant_order_blocked_by_fraud',
                    subjectType: 'restaurant_order',
                    subjectId: null,
                    context: [
                        'fraud_score' => $fraudResult['fraud_score'],
                        'indicators' => $fraudResult['indicators'],
                        'data' => $data,
                        'correlation_id' => $this->correlationId,
                        'user_id' => $data['user_id'] ?? null,
                        'tenant_id' => $data['tenant_id'] ?? null,
                    ],
                    correlationId: $this->correlationId
                ));
                throw new \RuntimeException('Order creation blocked by fraud detection');
            }

        return $this->db->transaction(function () use ($data) {
            // Находим или создаём гостя
            $guest = $this->findOrCreateGuest($data);

            // Резервируем столик если нужно
            if ($data['type'] === OrderType::DINE_IN && !empty($data['table_id'])) {
                $this->reserveTable($data['table_id']);
            }

            // Calculate delivery cost via GeoLogistics for delivery orders (Food sub-vertical)
            $deliveryFee = $data['delivery_fee'] ?? 0;
            if ($data['type'] === OrderType::DELIVERY && empty($deliveryFee) && !empty($data['delivery_address'])) {
                $restaurant = \Modules\Restaurant\Models\Restaurant::find($data['restaurant_id']);
                $restaurantAddress = $restaurant?->address ?? 'main_kitchen';

                $deliveryCalculation = $this->geoAdapter->calculateDeliveryForOrder([
                    'vertical' => 'restaurant',
                    'sub_vertical' => 'food',
                    'seller_address' => $restaurantAddress,
                    'buyer_address' => $data['delivery_address'],
                    'items' => $data['items'] ?? [],
                ]);

                $deliveryFee = $deliveryCalculation['cost'];
            }

            // Создаём заказ
            $order = Order::create([
                'tenant_id' => $data['tenant_id'],
                'business_group_id' => $data['business_group_id'] ?? null,
                'restaurant_id' => $data['restaurant_id'],
                'type' => $data['type'],
                'status' => OrderStatus::PENDING,
                'table_id' => $data['table_id'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'guest_id' => $guest->id,
                'waiter_id' => $data['waiter_id'] ?? null,
                'crm_deal_id' => $data['crm_deal_id'] ?? null,
                'order_time' => $data['order_time'] ?? now(),
                'estimated_ready_time' => $data['estimated_ready_time'] ?? null,
                'delivery_address' => $data['delivery_address'] ?? null,
                'delivery_phone' => $data['delivery_phone'] ?? null,
                'delivery_name' => $data['delivery_name'] ?? null,
                'delivery_lat' => $data['delivery_lat'] ?? null,
                'delivery_lon' => $data['delivery_lon'] ?? null,
                'delivery_fee' => $deliveryFee,
                'delivery_instructions' => $data['delivery_instructions'] ?? null,
                'special_requests' => $data['special_requests'] ?? null,
                'notes' => $data['notes'] ?? null,
                'correlation_id' => $this->correlationId,
            ]);

            // Создаём позиции заказа
            if (!empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $this->addOrderItem($order, $item);
                }
            }

            // Рассчитываем итоги
            $order->calculateTotals();
            $order->save();

            // Очищаем кэш
            $this->clearOrderCache($order);

            // Генерируем событие
            // TODO: Inject EventDispatcher and use it instead of facade

            // AUDIT LOG - Domain Event for Clean Architecture
            Event::dispatch(AuditEvent::created(
                subjectType: 'restaurant_order',
                subjectId: $order->id,
                newValues: [
                    'restaurant_id' => $order->restaurant_id,
                    'total_amount' => $order->total_amount,
                    'type' => $order->type->value,
                    'table_id' => $order->table_id,
                ],
                context: [
                    'correlation_id' => $this->correlationId,
                    'user_id' => $order->user_id,
                    'tenant_id' => $order->tenant_id,
                ],
                correlationId: $this->correlationId
            ));

            // Track behavioral event for analytics
            $this->behavioralTracker->capture(
                eventType: 'order_created',
                vertical: 'restaurant',
                targetId: (string) $order->id,
                payload: [
                    'restaurant_id' => $order->restaurant_id,
                    'order_type' => $order->type->value,
                    'table_id' => $order->table_id,
                    'total_amount' => $order->total_amount,
                    'delivery' => $order->type === OrderType::DELIVERY,
                ],
                monetaryValue: (float) $order->total_amount,
            );

            // Initialize realtime tracking for delivery orders (Food sub-vertical)
            if ($order->type === OrderType::DELIVERY) {
                $this->realtimeAdapter->startTracking([
                    'order_id' => $order->id,
                    'vertical' => 'restaurant',
                    'sub_vertical' => 'food',
                    'buyer_id' => $order->user_id,
                    'courier_id' => null, // Will be assigned later
                ], $this->correlationId);
            }

            // CRM INTEGRATION - sync order data to CRM (async via job would be better, but sync for now)
            if ($order->user_id || $guest) {
                try {
                    $crmData = [
                        'email' => $guest->email ?? null,
                        'first_name' => $guest->first_name ?? null,
                        'last_name' => $guest->last_name ?? null,
                        'phone' => $guest->phone ?? null,
                        'order_id' => $order->id,
                        'restaurant_id' => $order->restaurant_id,
                        'total_amount' => $order->total_amount,
                        'order_type' => $order->type->value,
                        'correlation_id' => $this->correlationId,
                    ];
                    // Note: In production, this should be dispatched to a queue job
                    // For now, we'll log it for integration
                    $this->logger->channel('audit')->info('CRM sync data prepared', $crmData);
                } catch (\Throwable $e) {
                    $this->logger->channel('audit')->error('CRM sync failed', [
                        'error' => $e->getMessage(),
                        'order_id' => $order->id,
                        'correlation_id' => $this->correlationId,
                    ]);
                }
            }

            return $order;
        });
        });
    }

    /**
     * Добавить позицию к заказу
     */
    public function addOrderItem(Order $order, array $itemData): OrderItem
    {
        // FRAUD CHECK
        $fraudResult = $this->fraud->checkRequest([
            'action' => 'restaurant_add_order_item',
            'user_id' => $order->user_id,
            'tenant_id' => $order->tenant_id,
            'ip_address' => request()->ip(),
        ]);

        if ($fraudResult['should_block']) {
            $this->logAction(
                action: 'blocked_by_fraud',
                entityType: 'restaurant_order',
                entityId: $order->id,
                context: [
                    'fraud_score' => $fraudResult['fraud_score'],
                    'indicators' => $fraudResult['indicators'],
                    'correlation_id' => $this->correlationId,
                ],
                userId: $order->user_id,
                tenantId: $order->tenant_id
            );
            throw new \RuntimeException('Order item addition blocked by fraud detection');
        }

        return $this->db->transaction(function () use ($order, $itemData) {
            $menuItem = \Modules\Restaurant\Models\MenuItem::find($itemData['menu_item_id']);

            $orderItem = OrderItem::create([
                'tenant_id' => $order->tenant_id,
                'business_group_id' => $order->business_group_id,
                'order_id' => $order->id,
                'menu_item_id' => $itemData['menu_item_id'],
                'item_name' => $menuItem?->name ?? $itemData['name'],
                'item_description' => $menuItem?->description ?? null,
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'] ?? $menuItem?->price ?? 0,
                'discount_amount' => $itemData['discount_amount'] ?? 0,
                'modifiers' => $itemData['modifiers'] ?? null,
                'addons' => $itemData['addons'] ?? null,
                'allergens' => $itemData['allergens'] ?? null,
                'special_instructions' => $itemData['special_instructions'] ?? null,
                'correlation_id' => $this->correlationId,
            ]);

            // Пересчитываем итоги заказа
            $order->calculateTotals();
            $order->save();

            // Очищаем кэш
            $this->clearOrderCache($order);

            // AUDIT LOG - using trait
            $this->logAction(
                action: 'item_added',
                entityType: 'restaurant_order',
                entityId: $order->id,
                context: [
                    'order_item_id' => $orderItem->id,
                    'menu_item_id' => $orderItem->menu_item_id,
                    'quantity' => $orderItem->quantity,
                    'unit_price' => $orderItem->unit_price,
                    'correlation_id' => $this->correlationId,
                ],
                userId: $order->user_id,
                tenantId: $order->tenant_id
            );

            return $orderItem;
        });
    }

    /**
     * Изменить статус заказа
     */
    public function changeOrderStatus(Order $order, OrderStatus $newStatus, ?string $reason = null): Order
    {
        // FRAUD CHECK
        $fraudResult = $this->fraud->checkRequest([
            'action' => 'restaurant_change_order_status',
            'user_id' => $order->user_id,
            'tenant_id' => $order->tenant_id,
            'ip_address' => request()->ip(),
        ]);

        if ($fraudResult['should_block']) {
            $this->logger->channel('audit')->warning('Order status change blocked by fraud detection', [
                'fraud_score' => $fraudResult['fraud_score'],
                'indicators' => $fraudResult['indicators'],
                'order_id' => $order->id,
                'correlation_id' => $this->correlationId,
            ]);
            throw new \RuntimeException('Order status change blocked by fraud detection');
        }

        if (!$order->status->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException("Cannot transition from {$order->status->value} to {$newStatus->value}");
        }

        $oldStatus = $order->status;

        return $this->db->transaction(function () use ($order, $newStatus, $oldStatus, $reason) {
            $order->transitionTo($newStatus);
            
            if ($reason) {
                $order->notes = ($order->notes ?? '') . "\nStatus change: {$reason}";
            }
            
            $order->save();

            // Освобождаем столик если заказ завершён
            if ($newStatus->isFinal() && $order->table_id) {
                $this->releaseTable($order->table_id);
            }

            // Stop realtime tracking for delivery orders when completed
            if ($newStatus === OrderStatus::DELIVERED && $order->type === OrderType::DELIVERY) {
                $this->realtimeAdapter->broadcastDeliveryCompleted($order->id, 'restaurant', $this->correlationId);
                $this->realtimeAdapter->stopTracking($order->id, 'restaurant', $this->correlationId);
            }

            // Stop tracking for cancelled delivery orders
            if ($newStatus === OrderStatus::CANCELLED && $order->type === OrderType::DELIVERY) {
                $this->realtimeAdapter->stopTracking($order->id, 'restaurant', $this->correlationId);
            }

            // Очищаем кэш
            $this->clearOrderCache($order);

            // Генерируем событие
            // TODO: Inject EventDispatcher and use it instead of facade

            // AUDIT LOG
            $this->logger->channel('audit')->info('Order status changed', [
                'order_id' => $order->id,
                'restaurant_id' => $order->restaurant_id,
                'tenant_id' => $order->tenant_id,
                'old_status' => $oldStatus->value,
                'new_status' => $newStatus->value,
                'reason' => $reason,
                'correlation_id' => $this->correlationId,
            ]);

            return Order::findOrFail($order->id);
        });
    }

    /**
     * Подтвердить заказ
     */
    public function confirmOrder(Order $order): Order
    {
        return $this->changeOrderStatus($order, OrderStatus::CONFIRMED);
    }

    /**
     * Отменить заказ
     */
    public function cancelOrder(Order $order, string $reason): Order
    {
        return $this->changeOrderStatus($order, OrderStatus::CANCELLED, $reason);
    }

    /**
     * Получить активные заказы для ресторана
     */
    public function getActiveOrders(int $restaurantId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->cache->tags(['restaurant', 'orders', "restaurant:{$restaurantId}"])
            ->remember("restaurant:{$restaurantId}:active_orders", 60, function () use ($restaurantId) {
                return Order::where('restaurant_id', $restaurantId)
                    ->active()
                    ->with(['table', 'guest', 'items'])
                    ->orderBy('order_time', 'desc')
                    ->get();
            });
    }

    /**
     * Получить статистику заказов за день
     */
    public function getDailyStatistics(int $restaurantId, ?string $date = null): array
    {
        $date = $date ?? today();

        $orders = Order::where('restaurant_id', $restaurantId)
            ->whereDate('order_time', $date)
            ->get();

        return [
            'total_orders' => $orders->count(),
            'total_revenue' => $orders->sum('total_amount'),
            'average_check' => $orders->avg('total_amount'),
            'by_type' => [
                'dine_in' => $orders->where('type', OrderType::DINE_IN)->count(),
                'delivery' => $orders->where('type', OrderType::DELIVERY)->count(),
                'pickup' => $orders->where('type', OrderType::PICKUP)->count(),
            ],
            'by_status' => $orders->groupBy('status.value')->map->count(),
        ];
    }

    // ========================
    // PRIVATE METHODS
    // ========================

    private function findOrCreateGuest(array $data): Guest
    {
        if (!empty($data['guest_id'])) {
            return Guest::find($data['guest_id']);
        }

        if (!empty($data['user_id'])) {
            $guest = Guest::where('user_id', $data['user_id'])
                ->where('restaurant_id', $data['restaurant_id'])
                ->first();

            if ($guest) {
                return $guest;
            }
        }

        return Guest::create([
            'tenant_id' => $data['tenant_id'],
            'business_group_id' => $data['business_group_id'] ?? null,
            'restaurant_id' => $data['restaurant_id'],
            'first_name' => $data['guest_first_name'] ?? null,
            'last_name' => $data['guest_last_name'] ?? null,
            'phone' => $data['guest_phone'] ?? null,
            'email' => $data['guest_email'] ?? null,
            'correlation_id' => $this->correlationId,
        ]);
    }

    private function reserveTable(int $tableId): void
    {
        $table = Table::find($tableId);
        if ($table && $table->canBeReserved()) {
            $table->reserve();
        }
    }

    private function releaseTable(int $tableId): void
    {
        $table = Table::find($tableId);
        if ($table && $table->canBeReleased()) {
            $table->release();
        }
    }

    private function clearOrderCache(Order $order): void
    {
        $this->cache->tags(['restaurant', 'orders', "restaurant:{$order->restaurant_id}"])->flush();
    }
}
