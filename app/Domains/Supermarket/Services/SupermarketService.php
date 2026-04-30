<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Services;

use App\Domains\Shared\Notifications\Services\OrderNotificationService;
use App\Domains\Shared\Realtime\RealtimeTrackingAdapter;
use App\Domains\Supermarket\Adapters\ColdChainAdapter;
use App\Domains\Supermarket\DTOs\CreateReturnData;
use App\Domains\Supermarket\DTOs\SupermarketOrderData;
use App\Domains\Supermarket\Enums\ReturnReason;
use App\Domains\Supermarket\Services\InventoryReservationService;
use App\Domains\Supermarket\Services\ReturnService;
use App\Domains\Supermarket\Services\HonestyMarkService;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Главный сервис вертикали Supermarket.
 *
 * Координирует все под-вертикали (Food, Grocery, Confectionery и т.д.)
 * и предоставляет унифицированный интерфейс для работы с заказами.
 *
 * Адаптеры:
 * - GeoLogisticsAdapter: расчёт доставки и слотов
 * - RealtimeTrackingAdapter: реалтайм-трекинг заказов
 * - InventoryService: управление остатками и резервированием
 * - ColdChainAdapter: мониторинг холодовой цепи для скоропортящихся товаров
 */
final readonly class SupermarketService
{
    use WithAuditLogging;
    use WithTelemetry;

    public function __construct(
        private readonly GeoLogisticsAdapter $geoAdapter,
        private readonly RealtimeTrackingAdapter $trackingAdapter,
        private readonly InventoryService $inventoryService,
        private readonly InventoryReservationService $inventoryReservationService,
        private readonly ColdChainAdapter $coldChainAdapter,
        private readonly FraudControlService $fraudControl,
        private readonly AuditService $audit,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly OrderNotificationService $notificationService,
        private readonly CRMEventDispatcher $crmEventDispatcher,
        private readonly HonestyMarkService $honestyMarkService,
    ) {}

    /**
     * Checkout: полный процесс оформления заказа с резервированием инвентаря.
     *
     * @param  SupermarketOrderData  $data
     * @return array{order_id: int, delivery_cost: int, eta: int, payment_data: array}
     */
    public function checkout(SupermarketOrderData $data): array
    {
        $correlationId = $data->correlationId ?? $this->generateCorrelationId();

        return $this->withSpan(
            'supermarket.checkout',
            function () use ($data, $correlationId) {
                $this->logger->info('Supermarket checkout started', [
                    'correlation_id' => $correlationId,
                    'user_id' => $data->userId,
                    'sub_vertical' => $data->subVertical,
                ]);

        return $this->db->transaction(function () use ($data, $correlationId) {
            // 1. Fraud check
            $this->fraudControl->check([
                'operation_type' => 'checkout',
                'vertical' => 'supermarket',
                'user_id' => $data->userId,
                'amount' => $data->getTotalAmount(),
                'correlation_id' => $correlationId,
            ]);

            // 2. Geo calculation for delivery
            $coldChainRequired = $this->coldChainAdapter->isColdChainRequired($data->items);
            $delivery = $this->geoAdapter->calculateDeliveryForOrder([
                'vertical' => 'supermarket',
                'sub_vertical' => $data->subVertical,
                'seller_address' => $data->sellerAddress,
                'buyer_address' => $data->buyerAddress,
                'items' => $data->items,
                'cold_chain' => $coldChainRequired,
            ]);

            // 3. Reserve inventory
            try {
                $reservationResult = $this->inventoryReservationService->reserveItems(
                    userId: $data->userId,
                    items: array_map(fn ($item) => [
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'warehouse_id' => $item['warehouse_id'] ?? $data->warehouseId ?? 1,
                    ], $data->items),
                    cartId: null,
                    orderId: null,
                    businessGroupId: $data->businessGroupId,
                );
            } catch (\Exception $e) {
                $this->logger->error('Inventory reservation failed during checkout', [
                    'user_id' => $data->userId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                throw $e;
            }

            // 4. Create order
            $orderData = [
                'user_id' => $data->userId,
                'tenant_id' => $data->tenantId,
                'business_group_id' => $data->businessGroupId,
                'items' => $data->items,
                'seller_address' => $data->sellerAddress,
                'buyer_address' => $data->buyerAddress,
                'delivery_slot' => $data->deliverySlot,
                'sub_vertical' => $data->subVertical,
                'warehouse_id' => $data->warehouseId,
                'correlation_id' => $correlationId,
            ];

            $orderResult = $this->createOrder($orderData);
            $orderId = $orderResult['order_id'];

            // 5. Update reservations with order_id
            foreach ($reservationResult['reservation_ids'] as $reservationId) {
                $this->db->table('inventory_reservations')
                    ->where('id', $reservationId)
                    ->update(['order_id' => $orderId]);
            }

            // 6. Prepare payment
            $paymentData = [
                'order_id' => $orderId,
                'amount' => $data->getTotalAmount(),
                'currency' => 'RUB',
                'vertical' => 'supermarket',
                'metadata' => [
                    'correlation_id' => $correlationId,
                    'delivery_cost' => $delivery['cost'],
                    'cold_chain_required' => $coldChainRequired,
                ],
            ];

            // 7. Dispatch confirmation job for after payment
            dispatch(new \App\Domains\Supermarket\Jobs\ConfirmInventoryJob(
                orderId: (string) $orderId,
                correlationId: $correlationId,
            ))->onQueue('supermarket-high');

            $this->logAction(
                action: 'checkout_completed',
                entityType: 'supermarket_order',
                entityId: $orderId,
                userId: $data->userId,
                context: [
                    'correlation_id' => $correlationId,
                    'sub_vertical' => $data->subVertical,
                    'total_amount' => $data->getTotalAmount(),
                    'delivery_cost' => $delivery['cost'],
                    'reservation_count' => count($reservationResult['reservation_ids']),
                ],
            );

            $this->logger->info('Supermarket checkout completed', [
                'order_id' => $orderId,
                'correlation_id' => $correlationId,
            ]);

            // Dispatch CRM event for order creation
            $order = \App\Domains\Supermarket\Models\SupermarketOrder::find($orderId);
            if ($order) {
                $this->crmEventDispatcher->dispatchOrderCreated(
                    OrderCreated::fromOrder($order)->data
                );
            }

            return [
                'order_id' => $orderId,
                'delivery_cost' => $delivery['cost'],
                'eta' => $delivery['eta'],
                'distance' => $delivery['distance'],
                'reservation_ids' => $reservationResult['reservation_ids'],
                'payment_data' => $paymentData,
                'cold_chain_required' => $coldChainRequired,
                'correlation_id' => $correlationId,
            ];
        });
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'checkout',
                userId: (string) $data->userId,
                tenantId: $data->tenantId,
                correlationId: $correlationId,
            ),
        );
    }

    /**
     * Подготовить чекаут: рассчитать доставку и доступные слоты.
     *
     * @param  array{items: array, seller_address: string, buyer_address: string, sub_vertical: string}  $cartData
     * @return array{delivery_cost: int, eta: int, available_slots: array, cold_chain_required: bool}
     */
    public function prepareCheckout(array $cartData): array
    {
        $correlationId = $this->generateCorrelationId();

        $this->logger->info('Supermarket checkout preparation started', [
            'correlation_id' => $correlationId,
            'sub_vertical' => $cartData['sub_vertical'] ?? null,
        ]);

        // Fraud check
        $this->fraudControl->check([
            'operation_type' => 'checkout_prepare',
            'vertical' => 'supermarket',
            'correlation_id' => $correlationId,
        ]);

        // Проверка холодовой цепи для товаров в корзине
        $coldChainRequired = $this->coldChainAdapter->isColdChainRequired($cartData['items'] ?? []);

        // Расчёт доставки через GeoLogistics
        $deliveryCalculation = $this->geoAdapter->calculateDeliveryForOrder([
            'vertical' => 'supermarket',
            'sub_vertical' => $cartData['sub_vertical'] ?? null,
            'seller_address' => $cartData['seller_address'],
            'buyer_address' => $cartData['buyer_address'],
            'items' => $cartData['items'] ?? [],
            'cold_chain' => $coldChainRequired,
        ]);

        // Получить доступные слоты доставки
        $availableSlots = $this->geoAdapter->getAvailableSlots(
            address: $cartData['buyer_address'],
            vertical: 'supermarket',
            subVertical: $cartData['sub_vertical'] ?? null,
        );

        // Проверка минимальной суммы заказа
        $orderTotal = $this->calculateOrderTotal($cartData['items'] ?? []);
        $minOrderAmount = config('verticals.supermarket.min_order_amount', 500);
        $minOrderMet = $orderTotal >= $minOrderAmount;

        $this->logAction(
            action: 'checkout_prepared',
            entityType: 'supermarket_checkout',
            entityId: null,
            userId: $cartData['user_id'] ?? null,
            context: [
                'correlation_id' => $correlationId,
                'sub_vertical' => $cartData['sub_vertical'] ?? null,
                'delivery_cost' => $deliveryCalculation['cost'],
                'order_total' => $orderTotal,
                'cold_chain_required' => $coldChainRequired,
            ],
        );

        return [
            'delivery_cost' => $deliveryCalculation['cost'],
            'eta' => $deliveryCalculation['eta'],
            'distance' => $deliveryCalculation['distance'],
            'available_slots' => $availableSlots,
            'cold_chain_required' => $coldChainRequired,
            'order_total' => $orderTotal,
            'min_order_amount' => $minOrderAmount,
            'min_order_met' => $minOrderMet,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Создать заказ с учётом доставки, резервирования товара и холодовой цепи.
     *
     * @param  array{user_id: int, items: array, seller_address: string, buyer_address: string, delivery_slot: string, sub_vertical: string, warehouse_id: int}  $orderData
     * @return array{order_id: int, delivery_cost: int, eta: int, reservation_ids: array}
     */
    public function createOrder(array $orderData): array
    {
        $correlationId = $this->generateCorrelationId();

        $this->logger->info('Supermarket order creation started', [
            'correlation_id' => $correlationId,
            'user_id' => $orderData['user_id'],
            'sub_vertical' => $orderData['sub_vertical'] ?? null,
        ]);

        return $this->db->transaction(function () use ($orderData, $correlationId) {
            // Fraud check
            $orderTotal = $this->calculateOrderTotal($orderData['items'] ?? []);
            $this->fraudControl->check([
                'operation_type' => 'order_create',
                'vertical' => 'supermarket',
                'user_id' => $orderData['user_id'],
                'amount' => $orderTotal,
                'correlation_id' => $correlationId,
            ]);

            // Проверка минимальной суммы заказа
            $minOrderAmount = config('verticals.supermarket.min_order_amount', 500);
            if ($orderTotal < $minOrderAmount) {
                throw new \RuntimeException(sprintf(
                    'Minimum order amount not met. Required: %d, Current: %d',
                    $minOrderAmount,
                    $orderTotal
                ));
            }

            // Резервирование товаров через InventoryReservationService с rollback
            $reservationIds = [];
            try {
                $reservationResult = $this->inventoryReservationService->reserveItems(
                    userId: $orderData['user_id'],
                    items: $orderData['items'],
                    cartId: $orderData['cart_id'] ?? null,
                    orderId: null,
                    businessGroupId: $orderData['business_group_id'] ?? null,
                );
                $reservationIds = $reservationResult['reservation_ids'];
            } catch (\Exception $e) {
                $this->logger->error('Inventory reservation failed, rolling back order creation', [
                    'user_id' => $orderData['user_id'],
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                throw $e;
            }

            // Расчёт доставки
            $coldChainRequired = $this->coldChainAdapter->isColdChainRequired($orderData['items'] ?? []);
            $deliveryCalculation = $this->geoAdapter->calculateDeliveryForOrder([
                'vertical' => 'supermarket',
                'sub_vertical' => $orderData['sub_vertical'] ?? null,
                'seller_address' => $orderData['seller_address'],
                'buyer_address' => $orderData['buyer_address'],
                'items' => $orderData['items'] ?? [],
                'cold_chain' => $coldChainRequired,
            ]);

            // Создание заказа (делегируется в под-вертикальный сервис)
            $orderId = null;
            try {
                $subVerticalService = $this->getSubVerticalService($orderData['sub_vertical'] ?? null);
                if ($subVerticalService) {
                    $orderId = $subVerticalService->createOrder($orderData, $deliveryCalculation, $correlationId);
                } else {
                    $orderId = $this->db->table('supermarket_orders')->insertGetId([
                        'uuid' => (string) Str::uuid(),
                        'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : null,
                        'user_id' => $orderData['user_id'],
                        'sub_vertical' => $orderData['sub_vertical'] ?? null,
                        'status' => 'pending',
                        'total_amount' => $orderTotal,
                        'delivery_cost' => $deliveryCalculation['cost'],
                        'delivery_eta' => $deliveryCalculation['eta'],
                        'delivery_address' => $orderData['buyer_address'],
                        'delivery_slot' => $orderData['delivery_slot'],
                        'cold_chain_required' => $coldChainRequired,
                        'correlation_id' => $correlationId,
                        'created_at' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                // Rollback reservations if order creation fails
                $this->logger->error('Order creation failed, releasing reservations', [
                    'user_id' => $orderData['user_id'],
                    'reservation_ids' => $reservationIds,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                $this->inventoryReservationService->releaseReservations($reservationIds, $orderData['user_id']);
                throw $e;
            }

            // Обновление резервов с ID заказа
            foreach ($reservationIds as $reservationId) {
                $this->db->table('inventory_reservations')
                    ->where('id', $reservationId)
                    ->update(['order_id' => $orderId]);
            }

            // Регистрация холодовой цепи если требуется
            if ($coldChainRequired) {
                $this->coldChainAdapter->registerOrderForMonitoring($orderId, [
                    'items' => $orderData['items'],
                    'delivery_eta' => $deliveryCalculation['eta'],
                    'correlation_id' => $correlationId,
                ]);
            }

            $this->logCreated(
                entityType: 'supermarket_order',
                entityId: $orderId,
                userId: $orderData['user_id'],
                context: [
                    'correlation_id' => $correlationId,
                    'sub_vertical' => $orderData['sub_vertical'] ?? null,
                    'total_amount' => $orderTotal,
                    'delivery_cost' => $deliveryCalculation['cost'],
                    'cold_chain_required' => $coldChainRequired,
                    'reservation_count' => count($reservationIds),
                ],
            );

            $this->logger->info('Supermarket order created', [
                'order_id' => $orderId,
                'correlation_id' => $correlationId,
                'delivery_cost' => $deliveryCalculation['cost'],
                'reservation_count' => count($reservationIds),
            ]);

            // Запуск реалтайм-трекинга заказа
            $trackingSession = $this->trackingAdapter->startTracking([
                'order_id' => $orderId,
                'vertical' => 'supermarket',
                'sub_vertical' => $orderData['sub_vertical'] ?? null,
                'courier_id' => null, // будет назначен позже
                'buyer_id' => $orderData['user_id'],
            ], $correlationId);

            // Отправка уведомлений о создании заказа
            $order = \App\Domains\Supermarket\Models\SupermarketOrder::find($orderId);
            if ($order) {
                $this->notificationService->notify($order, 'created');
            }

            return [
                'order_id' => $orderId,
                'delivery_cost' => $deliveryCalculation['cost'],
                'eta' => $deliveryCalculation['eta'],
                'distance' => $deliveryCalculation['distance'],
                'reservation_ids' => $reservationIds,
                'cold_chain_required' => $coldChainRequired,
                'tracking_session' => $trackingSession,
                'correlation_id' => $correlationId,
            ];
        });
    }

    /**
     * Подтвердить оплату и финализировать заказ.
     *
     * @param  int  $orderId
     * @param  string  $paymentId
     * @param  int  $userId
     * @return array{success: bool, order_id: int}
     */
    public function confirmPayment(int $orderId, string $paymentId, int $userId): array
    {
        $correlationId = $this->generateCorrelationId();

        return $this->db->transaction(function () use ($orderId, $paymentId, $userId, $correlationId) {
            // Fraud check
            $this->fraudControl->check([
                'operation_type' => 'payment_confirm',
                'vertical' => 'supermarket',
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ]);

            // Обновление статуса заказа
            $order = \App\Domains\Supermarket\Models\SupermarketOrder::find($orderId);
            $oldStatus = $order ? $order->status : null;

            $this->db->table('supermarket_orders')
                ->where('id', $orderId)
                ->update([
                    'status' => 'paid',
                    'payment_id' => $paymentId,
                    'updated_at' => now(),
                ]);

            // Dispatch CRM event for status change
            if ($order && $oldStatus !== 'paid') {
                $order->refresh();
                $this->crmEventDispatcher->dispatchOrderStatusChanged(
                    OrderStatusChanged::fromOrder($order, $oldStatus)->data
                );
            }

            $this->logPayment(
                entityType: 'supermarket_order',
                entityId: $orderId,
                userId: $userId,
                amount: $this->getOrderTotal($orderId),
                context: [
                    'correlation_id' => $correlationId,
                    'payment_id' => $paymentId,
                    'status' => 'success',
                ],
            );

            // Dispatch cashback job after successful payment
            $order = \App\Domains\Supermarket\Models\SupermarketOrder::find($orderId);
            if ($order) {
                dispatch(new ProcessCashbackJob($order))->onQueue('supermarket-high');
            }

            // Withdraw Honest Mark for products with marks
            $this->withdrawOrderMarks($orderId, $correlationId);

            return [
                'success' => true,
                'order_id' => $orderId,
            ];
        });
    }

    /**
     * Отменить заказ и освободить резервы.
     *
     * @param  int  $orderId
     * @param  int  $userId
     * @return array{success: bool}
     */
    public function cancelOrder(int $orderId, int $userId): array
    {
        $correlationId = $this->generateCorrelationId();

        return $this->db->transaction(function () use ($orderId, $userId, $correlationId) {
            // Получение резервов заказа
            $reservations = $this->db->table('inventory_reservations')
                ->where('order_id', $orderId)
                ->get();

            $reservationIds = $reservations->pluck('id')->toArray();

            // Освобождение резервов через InventoryReservationService
            if (!empty($reservationIds)) {
                $this->inventoryReservationService->releaseReservations($reservationIds, $userId);
            }

            // Обновление статуса заказа
            $order = \App\Domains\Supermarket\Models\SupermarketOrder::find($orderId);
            $oldStatus = $order ? $order->status : null;

            $this->db->table('supermarket_orders')
                ->where('id', $orderId)
                ->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'updated_at' => now(),
                ]);

            // Dispatch CRM event for status change
            if ($order && $oldStatus !== 'cancelled') {
                $order->refresh();
                $this->crmEventDispatcher->dispatchOrderStatusChanged(
                    OrderStatusChanged::fromOrder($order, $oldStatus)->data
                );
                $this->crmEventDispatcher->dispatchOrderCancelled([
                    'order_id' => $orderId,
                    'external_id' => $order->uuid,
                    'cancelled_at' => now()->toIso8601String(),
                    'reason' => 'user_requested',
                    'correlation_id' => $correlationId,
                ]);
            }

            // Остановка мониторинга холодовой цепи
            $this->coldChainAdapter->unregisterOrderFromMonitoring($orderId);

            // Остановка трекинга
            $this->trackingAdapter->stopTracking($orderId, 'supermarket', $correlationId);

            $this->logAction(
                action: 'order_cancelled',
                entityType: 'supermarket_order',
                entityId: $orderId,
                userId: $userId,
                context: [
                    'correlation_id' => $correlationId,
                    'reservations_released' => count($reservationIds),
                ],
            );

            return ['success' => true];
        });
    }

    /**
     * Проверить доступность доставки для адреса.
     */
    public function checkDeliveryAvailability(string $address): bool
    {
        return $this->geoAdapter->isAddressInDeliveryZone($address, 'supermarket');
    }

    /**
     * Получить популярные товары (кэшированные).
     *
     * @param  string|null  $subVertical
     * @param  int  $limit
     * @return array<int, array>
     */
    public function getPopularProducts(?string $subVertical = null, int $limit = 20): array
    {
        $subVertical = $subVertical ?? 'grocery_and_delivery';
        $cacheKey = "supermarket:popular_products:{$subVertical}:{$limit}";

        return Cache::tags(['supermarket', 'products', $subVertical])
            ->remember($cacheKey, now()->addMinutes(30), function () use ($subVertical, $limit) {
                return $this->db->table('inventory_items')
                    ->join('products', 'inventory_items.product_id', '=', 'products.id')
                    ->where('inventory_items.quantity', '>', 0)
                    ->where('inventory_items.warehouse_id', 1)
                    ->where('products.sub_vertical', $subVertical)
                    ->orderBy('products.popularity_score', 'desc')
                    ->orderBy('inventory_items.quantity', 'desc')
                    ->limit($limit)
                    ->select([
                        'products.id',
                        'products.name',
                        'products.price',
                        'products.category',
                        'products.image_url',
                        'inventory_items.quantity as available',
                        'products.requires_cold_chain',
                    ])
                    ->get()
                    ->toArray();
            });
    }

    /**
     * Получить доступные слоты доставки (кэшированные).
     *
     * @param  string  $address
     * @param  string|null  $subVertical
     * @return array
     */
    public function getCachedDeliverySlots(string $address, ?string $subVertical = null): array
    {
        $subVertical = $subVertical ?? 'grocery_and_delivery';
        $cacheKey = "supermarket:delivery_slots:" . md5($address . $subVertical);

        return Cache::tags(['supermarket', 'delivery_slots', $subVertical])
            ->remember($cacheKey, now()->addMinutes(15), function () use ($address, $subVertical) {
                return $this->geoAdapter->getAvailableSlots(
                    address: $address,
                    vertical: 'supermarket',
                    subVertical: $subVertical,
                );
            });
    }

    /**
     * Инвалидировать кэш популярных продуктов.
     */
    public function invalidatePopularProductsCache(?string $subVertical = null): void
    {
        if ($subVertical) {
            Cache::tags(['supermarket', 'products', $subVertical])->flush();
        } else {
            Cache::tags(['supermarket', 'products'])->flush();
        }
    }

    /**
     * Инвалидировать кэш слотов доставки.
     */
    public function invalidateDeliverySlotsCache(?string $subVertical = null): void
    {
        if ($subVertical) {
            Cache::tags(['supermarket', 'delivery_slots', $subVertical])->flush();
        } else {
            Cache::tags(['supermarket', 'delivery_slots'])->flush();
        }
    }

    /**
     * Рассчитать общую сумму заказа.
     */
    private function calculateOrderTotal(array $items): int
    {
        return array_sum(array_map(fn ($item) => $item['price'] * $item['quantity'], $items));
    }

    /**
     * Получить сумму заказа из БД.
     */
    private function getOrderTotal(int $orderId): int
    {
        return (int) $this->db->table('supermarket_orders')
            ->where('id', $orderId)
            ->value('total_amount');
    }

    /**
     * Получить сервис под-вертикали по названию.
     *
     * @param  string|null  $subVertical
     * @return object|null
     */
    private function getSubVerticalService(?string $subVertical): ?object
    {
        $subVerticalServices = [
            'meat_shops' => 'App\\Domains\\Supermarket\\SubVerticals\\MeatShops\\Services\\MeatShopsService',
            'farm_direct' => 'App\\Domains\\Supermarket\\SubVerticals\\FarmDirect\\Services\\FarmDirectService',
            'vegan_products' => 'App\\Domains\\Supermarket\\SubVerticals\\VeganProducts\\Services\\VeganProductsService',
            'confectionery' => 'App\\Domains\\Supermarket\\SubVerticals\\Confectionery\\Services\\ConfectioneryService',
            'grocery_and_delivery' => 'App\\Domains\\Supermarket\\SubVerticals\\GroceryAndDelivery\\Services\\GroceryAndDeliveryService',
            'food' => 'App\\Domains\\Supermarket\\SubVerticals\\Food\\Services\\FoodService',
            'office_catering' => 'App\\Domains\\Supermarket\\SubVerticals\\OfficeCatering\\Services\\OfficeCateringService',
        ];

        $serviceClass = $subVerticalServices[strtolower($subVertical ?? '')] ?? null;
        
        if ($serviceClass && class_exists($serviceClass)) {
            return app($serviceClass);
        }

        return null;
    }

    // Cart Management Methods

    /**
     * Get user's cart with 20-minute reservation.
     */
    public function getCart(int $userId, ?string $tenantId): array
    {
        $cart = $this->db->table('cart_items')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('reservation_expires_at', '>', now())
            ->get();

        $items = $cart->map(function ($item) {
            $product = $this->db->table('products')
                ->where('id', $item->product_id)
                ->first();

            return [
                'id' => $item->id,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'sub_vertical' => $product->sub_vertical,
                    'requires_cold_chain' => (bool) $product->requires_cold_chain,
                    'image' => $product->image_url,
                ],
                'quantity' => $item->quantity,
                'total' => $product->price * $item->quantity,
                'attributes' => json_decode($item->attributes ?? '[]', true),
            ];
        })->toArray();

        $coldChainRequired = $cart->pluck('product_id')
            ->map(fn($id) => $this->db->table('products')->where('id', $id)->value('requires_cold_chain'))
            ->contains(true);

        return [
            'items' => $items,
            'reservation_expires_at' => $cart->max('reservation_expires_at'),
            'subtotal' => array_sum(array_column($items, 'total')),
            'cold_chain_required' => $coldChainRequired,
        ];
    }

    /**
     * Add product to cart with 20-minute reservation.
     */
    public function addToCart(int $userId, ?string $tenantId, string $productId, int $quantity, array $attributes = []): array
    {
        $correlationId = $this->generateCorrelationId();

        return $this->db->transaction(function () use ($userId, $tenantId, $productId, $quantity, $attributes, $correlationId) {
            // Check if product exists
            $product = $this->db->table('products')->where('id', $productId)->first();
            if (!$product) {
                throw new \RuntimeException('Product not found');
            }

            // Check if item already in cart
            $existingItem = $this->db->table('cart_items')
                ->where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->where('product_id', $productId)
                ->where('reservation_expires_at', '>', now())
                ->first();

            if ($existingItem) {
                // Update quantity
                $this->db->table('cart_items')
                    ->where('id', $existingItem->id)
                    ->update([
                        'quantity' => $existingItem->quantity + $quantity,
                        'reservation_expires_at' => now()->addMinutes(20),
                    ]);
            } else {
                // Create new cart item with reservation
                $this->db->table('cart_items')->insert([
                    'user_id' => $userId,
                    'tenant_id' => $tenantId,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'attributes' => json_encode($attributes),
                    'reservation_expires_at' => now()->addMinutes(20),
                    'created_at' => now(),
                ]);

                // Reserve inventory
                try {
                    $this->inventoryReservationService->reserveItems(
                        userId: $userId,
                        items: [[
                            'product_id' => $productId,
                            'quantity' => $quantity,
                            'warehouse_id' => 1,
                        ]],
                        cartId: null,
                        orderId: null,
                        businessGroupId: null,
                    );
                } catch (\Exception $e) {
                    $this->logger->error('Failed to reserve inventory for cart item', [
                        'user_id' => $userId,
                        'product_id' => $productId,
                        'error' => $e->getMessage(),
                        'correlation_id' => $correlationId,
                    ]);
                    throw $e;
                }
            }

            $this->logAction(
                action: 'item_added_to_cart',
                entityType: 'cart',
                entityId: null,
                userId: $userId,
                context: [
                    'correlation_id' => $correlationId,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                ],
            );

            return $this->getCart($userId, $tenantId);
        });
    }

    /**
     * Update cart item quantity.
     */
    public function updateCartItem(int $userId, ?string $tenantId, string $itemId, int $quantity): array
    {
        $correlationId = $this->generateCorrelationId();

        return $this->db->transaction(function () use ($userId, $tenantId, $itemId, $quantity, $correlationId) {
            $this->db->table('cart_items')
                ->where('id', $itemId)
                ->where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->update([
                    'quantity' => $quantity,
                    'reservation_expires_at' => now()->addMinutes(20),
                ]);

            $this->logAction(
                action: 'cart_item_updated',
                entityType: 'cart',
                entityId: $itemId,
                userId: $userId,
                context: [
                    'correlation_id' => $correlationId,
                    'quantity' => $quantity,
                ],
            );

            return $this->getCart($userId, $tenantId);
        });
    }

    /**
     * Remove item from cart.
     */
    public function removeFromCart(int $userId, ?string $tenantId, string $itemId): array
    {
        $correlationId = $this->generateCorrelationId();

        return $this->db->transaction(function () use ($userId, $tenantId, $itemId, $correlationId) {
            $item = $this->db->table('cart_items')
                ->where('id', $itemId)
                ->where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->first();

            if ($item) {
                // Release reservation
                $this->inventoryReservationService->releaseReservations(
                    [$itemId],
                    $userId
                );

                $this->db->table('cart_items')
                    ->where('id', $itemId)
                    ->delete();

                $this->logAction(
                    action: 'item_removed_from_cart',
                    entityType: 'cart',
                    entityId: $itemId,
                    userId: $userId,
                    context: [
                        'correlation_id' => $correlationId,
                    ],
                );
            }

            return $this->getCart($userId, $tenantId);
        });
    }

    /**
     * Clear user's cart.
     */
    public function clearCart(int $userId, ?string $tenantId): void
    {
        $correlationId = $this->generateCorrelationId();

        $this->db->transaction(function () use ($userId, $tenantId, $correlationId) {
            $items = $this->db->table('cart_items')
                ->where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->get();

            $itemIds = $items->pluck('id')->toArray();

            if (!empty($itemIds)) {
                // Release all reservations
                $this->inventoryReservationService->releaseReservations($itemIds, $userId);

                $this->db->table('cart_items')
                    ->whereIn('id', $itemIds)
                    ->delete();

                $this->logAction(
                    action: 'cart_cleared',
                    entityType: 'cart',
                    entityId: null,
                    userId: $userId,
                    context: [
                        'correlation_id' => $correlationId,
                        'items_count' => count($itemIds),
                    ],
                );
            }
        });
    }

    /**
     * Get delivery slots for checkout.
     */
    public function getDeliverySlots(int $userId, ?string $tenantId): array
    {
        $cart = $this->getCart($userId, $tenantId);

        if (empty($cart['items'])) {
            return [];
        }

        $slots = $this->geoAdapter->getAvailableSlots(
            address: $cart['items'][0]['product']['address'] ?? 'Москва',
            vertical: 'supermarket',
            subVertical: $cart['items'][0]['product']['sub_vertical'] ?? null,
        );

        return $slots;
    }

    /**
     * Создать возврат для заказа.
     *
     * @param int $orderId ID заказа
     * @param int $buyerId ID покупателя
     * @param array $items Товары для возврата
     * @param ReturnReason $reasonType Причина возврата
     * @param string|null $comment Комментарий
     * @param bool $isColdChain Была ли холодная цепь
     * @param string $returnMethod Способ возврата
     * @param array|null $images Фото доказательства
     * @return array
     */
    public function createReturn(
        int $orderId,
        int $buyerId,
        array $items,
        ReturnReason $reasonType,
        ?string $comment = null,
        bool $isColdChain = false,
        string $returnMethod = 'pickup',
        ?array $images = null
    ): array {
        $correlationId = $this->generateCorrelationId();

        $this->logger->info('Creating return via SupermarketService', [
            'correlation_id' => $correlationId,
            'order_id' => $orderId,
            'buyer_id' => $buyerId,
            'reason_type' => $reasonType->value,
        ]);

        // Fraud check
        $this->fraudControl->check([
            'operation_type' => 'return_create',
            'vertical' => 'supermarket',
            'user_id' => $buyerId,
            'amount' => array_sum(array_map(fn ($item) => $item['price'] * $item['quantity'], $items)),
            'correlation_id' => $correlationId,
        ]);

        // Получить заказ для определения sub_vertical
        $order = SupermarketOrder::findOrFail($orderId);

        // Создать DTO для возврата
        $data = new CreateReturnData(
            orderId: $orderId,
            buyerId: $buyerId,
            sellerId: $order->tenant?->user_id ?? null,
            reasonType: $reasonType,
            comment: $comment,
            items: $items,
            isColdChain: $isColdChain,
            returnMethod: $returnMethod,
            images: $images,
            subVertical: $order->sub_vertical,
            correlationId: $correlationId,
        );

        // Создать возврат через ReturnService
        $return = $this->returnService->createReturn($data);

        // Отправить уведомление о создании возврата
        $this->notificationService->notifyReturnCreated($return);

        $this->logAction(
            action: 'return_created_via_supermarket',
            entityType: 'return',
            entityId: $return->id,
            userId: $buyerId,
            context: [
                'order_id' => $orderId,
                'correlation_id' => $correlationId,
                'total_amount' => $return->total_amount,
                'reason_type' => $reasonType->value,
            ]
        );

        return [
            'return_id' => $return->id,
            'status' => $return->status,
            'total_amount' => $return->total_amount,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Одобрить возврат.
     *
     * @param int $returnId ID возврата
     * @return array
     */
    public function approveReturn(int $returnId): array
    {
        $correlationId = $this->generateCorrelationId();

        $this->logger->info('Approving return via SupermarketService', [
            'correlation_id' => $correlationId,
            'return_id' => $returnId,
        ]);

        $return = \App\Domains\Supermarket\Models\Return::with('order')->findOrFail($returnId);
        $approvedReturn = $this->returnService->approve($return);

        // Отправить уведомление об одобрении
        $this->notificationService->notifyReturnApproved($approvedReturn);

        $this->logAction(
            action: 'return_approved_via_supermarket',
            entityType: 'return',
            entityId: $returnId,
            context: [
                'correlation_id' => $correlationId,
                'refund_amount' => $approvedReturn->refund_amount,
            ]
        );

        return [
            'return_id' => $approvedReturn->id,
            'status' => $approvedReturn->status,
            'refund_amount' => $approvedReturn->refund_amount,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Отклонить возврат.
     *
     * @param int $returnId ID возврата
     * @param string $reason Причина отклонения
     * @return array
     */
    public function rejectReturn(int $returnId, string $reason): array
    {
        $correlationId = $this->generateCorrelationId();

        $this->logger->info('Rejecting return via SupermarketService', [
            'correlation_id' => $correlationId,
            'return_id' => $returnId,
            'reject_reason' => $reason,
        ]);

        $return = \App\Domains\Supermarket\Models\Return::findOrFail($returnId);
        $rejectedReturn = $this->returnService->reject($return, $reason);

        // Отправить уведомление об отклонении
        $this->notificationService->notifyReturnRejected($rejectedReturn);

        $this->logAction(
            action: 'return_rejected_via_supermarket',
            entityType: 'return',
            entityId: $returnId,
            context: [
                'correlation_id' => $correlationId,
                'reject_reason' => $reason,
            ]
        );

        return [
            'return_id' => $rejectedReturn->id,
            'status' => $rejectedReturn->status,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Получить возвраты покупателя.
     *
     * @param int $buyerId ID покупателя
     * @param int|null $limit Лимит
     * @return array
     */
    public function getBuyerReturns(int $buyerId, ?int $limit = null): array
    {
        $returns = $this->returnService->getBuyerReturns($buyerId, $limit);

        return [
            'returns' => $returns->map(fn ($return) => [
                'id' => $return->id,
                'order_id' => $return->order_id,
                'status' => $return->status,
                'total_amount' => $return->total_amount,
                'created_at' => $return->created_at,
            ])->toArray(),
        ];
    }

    /**
     * Вывод маркировок из оборота после успешной оплаты заказа.
     *
     * @param int $orderId
     * @param string $correlationId
     * @return void
     */
    private function withdrawOrderMarks(int $orderId, string $correlationId): void
    {
        if (!config('honestysign.enabled', true)) {
            return;
        }

        try {
            // Get order items with product IDs
            $orderItems = $this->db->table('supermarket_order_items')
                ->where('order_id', $orderId)
                ->get();

            foreach ($orderItems as $item) {
                // Find active mark for this product
                $mark = \App\Domains\Supermarket\Models\ProductMark::where('product_id', $item->product_id)
                    ->where('status', 'in_circulation')
                    ->first();

                if ($mark) {
                    $withdrawResult = $this->honestyMarkService->withdraw($mark, [
                        'quantity' => $item->quantity,
                        'price' => $item->price_per_unit,
                    ]);

                    $this->logger->info('Honest Mark withdrawal attempt', [
                        'order_id' => $orderId,
                        'product_id' => $item->product_id,
                        'mark_id' => $mark->id,
                        'success' => $withdrawResult,
                        'correlation_id' => $correlationId,
                    ]);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Honest Mark withdrawal failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            // Don't fail the order if mark withdrawal fails
        }
    }

    /**
     * Создать товар с маркировкой Честный ЗНАК.
     *
     * @param array $productData
     * @param array|null $markData
     * @return array
     */
    public function createProductWithMark(array $productData, ?array $markData = null): array
    {
        $correlationId = $this->generateCorrelationId();

        return $this->db->transaction(function () use ($productData, $markData, $correlationId) {
            // Create product
            $productId = $this->db->table('products')->insertGetId([
                'name' => $productData['name'],
                'description' => $productData['description'] ?? null,
                'price' => $productData['price'],
                'category' => $productData['category'] ?? null,
                'sub_vertical' => $productData['sub_vertical'] ?? 'grocery_and_delivery',
                'image_url' => $productData['image_url'] ?? null,
                'requires_cold_chain' => $productData['requires_cold_chain'] ?? false,
                'tenant_id' => $productData['tenant_id'] ?? (function_exists('tenant') && tenant() ? tenant()->id : null),
                'created_at' => now(),
            ]);

            // Create mark if provided
            if ($markData && config('honestysign.enabled', true)) {
                try {
                    $this->honestyMarkService->createProductMark([
                        'product_id' => $productId,
                        'gtin' => $markData['gtin'],
                        'data_matrix' => $markData['data_matrix'],
                        'batch_number' => $markData['batch_number'] ?? null,
                        'production_date' => $markData['production_date'] ?? null,
                        'expiration_date' => $markData['expiration_date'] ?? null,
                        'tenant_id' => $productData['tenant_id'] ?? (function_exists('tenant') && tenant() ? tenant()->id : null),
                    ]);

                    $this->logAction(
                        action: 'product_mark_created',
                        entityType: 'product_mark',
                        entityId: $productId,
                        userId: $productData['user_id'] ?? null,
                        context: [
                            'correlation_id' => $correlationId,
                            'gtin' => $markData['gtin'],
                        ],
                    );
                } catch (\App\Domains\Supermarket\Exceptions\HonestyMarkException $e) {
                    // Rollback product creation if mark validation fails
                    $this->db->table('products')->where('id', $productId)->delete();
                    throw $e;
                }
            }

            // Create certificate if provided
            if (isset($productData['certificate']) && config('honestysign.enabled', true)) {
                try {
                    $this->honestyMarkService->createCertificate([
                        'product_id' => $productId,
                        'certificate_number' => $productData['certificate']['certificate_number'],
                        'type' => $productData['certificate']['type'] ?? 'certificate',
                        'issued_by' => $productData['certificate']['issued_by'],
                        'valid_from' => $productData['certificate']['valid_from'] ?? now(),
                        'valid_until' => $productData['certificate']['valid_until'],
                        'file_path' => $productData['certificate']['file_path'] ?? null,
                        'tenant_id' => $productData['tenant_id'] ?? (function_exists('tenant') && tenant() ? tenant()->id : null),
                    ]);

                    $this->logAction(
                        action: 'product_certificate_created',
                        entityType: 'certificate',
                        entityId: $productId,
                        userId: $productData['user_id'] ?? null,
                        context: [
                            'correlation_id' => $correlationId,
                            'certificate_number' => $productData['certificate']['certificate_number'],
                        ],
                    );
                } catch (\App\Domains\Supermarket\Exceptions\HonestyMarkException $e) {
                    // Rollback if certificate validation fails
                    $this->db->table('products')->where('id', $productId)->delete();
                    $this->db->table('product_marks')->where('product_id', $productId)->delete();
                    throw $e;
                }
            }

            $this->logCreated(
                entityType: 'product',
                entityId: $productId,
                userId: $productData['user_id'] ?? null,
                context: [
                    'correlation_id' => $correlationId,
                    'name' => $productData['name'],
                    'has_mark' => !empty($markData),
                ],
            );

            return [
                'product_id' => $productId,
                'correlation_id' => $correlationId,
            ];
        });
    }

    /**
     * Проверить маркировку товара.
     *
     * @param string $dataMatrix
     * @param string $gtin
     * @return array
     */
    public function validateProductMark(string $dataMatrix, string $gtin): array
    {
        return $this->honestyMarkService->validateMark($dataMatrix, $gtin);
    }

    /**
     * Проверить сертификат товара.
     *
     * @param string $certificateNumber
     * @return array
     */
    public function validateProductCertificate(string $certificateNumber): array
    {
        return $this->honestyMarkService->validateCertificate($certificateNumber);
    }
}
