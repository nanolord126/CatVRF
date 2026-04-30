<?php

declare(strict_types=1);

namespace App\Http\Controllers\Supermarket;

use App\Domains\Supermarket\Services\InventoryReservationService;
use App\Domains\Supermarket\Services\SupermarketService;
use App\Domains\Supermarket\Adapters\ColdChainAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Psr\Log\LoggerInterface;

final readonly class SupermarketCheckoutController
{
    public function __construct(
        private readonly SupermarketService $supermarketService,
        private readonly InventoryReservationService $inventoryReservationService,
        private readonly ColdChainAdapter $coldChainAdapter,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Получить популярные товары (кэшированные).
     */
    public function getPopularProducts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sub_vertical' => 'nullable|string|in:grocery_and_delivery,meat_shops,farm_direct,vegan_products,confectionery,food,office_catering',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $subVertical = $validated['sub_vertical'] ?? 'grocery_and_delivery';
        $limit = (int) ($validated['limit'] ?? 20);

        $cacheKey = "supermarket:popular_products:{$subVertical}:{$limit}";

        try {
            $products = Cache::tags(['supermarket', 'products', $subVertical])
                ->remember($cacheKey, now()->addMinutes(30), function () use ($subVertical, $limit) {
                    return DB::table('inventory_items')
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
                        ->get();
                });

            return response()->json([
                'success' => true,
                'data' => $products,
                'cached_at' => now(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch popular products', [
                'error' => $e->getMessage(),
                'sub_vertical' => $subVertical,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch products',
            ], 500);
        }
    }

    /**
     * Получить категории товаров (кэшированные).
     */
    public function getCategories(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sub_vertical' => 'nullable|string|in:grocery_and_delivery,meat_shops,farm_direct,vegan_products,confectionery,food,office_catering',
        ]);

        $subVertical = $validated['sub_vertical'] ?? 'grocery_and_delivery';
        $cacheKey = "supermarket:categories:{$subVertical}";

        try {
            $categories = Cache::tags(['supermarket', 'categories', $subVertical])
                ->remember($cacheKey, now()->addHours(2), function () use ($subVertical) {
                    return DB::table('products')
                        ->where('sub_vertical', $subVertical)
                        ->whereNotNull('category')
                        ->select('category')
                        ->distinct()
                        ->orderBy('category')
                        ->get()
                        ->pluck('category');
                });

            return response()->json([
                'success' => true,
                'data' => $categories,
                'cached_at' => now(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch categories', [
                'error' => $e->getMessage(),
                'sub_vertical' => $subVertical,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch categories',
            ], 500);
        }
    }

    /**
     * Подготовить чекаут.
     */
    public function prepareCheckout(Request $request): JsonResponse
    {
        // Authorization check
        Gate::authorize('prepareCheckout', $request->user());

        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'items' => 'required|array|min:1|max:50',
            'items.*.product_id' => 'required|integer|min:1',
            'items.*.quantity' => 'required|integer|min:1|max:100',
            'items.*.price' => 'required|numeric|min:0|max:100000',
            'items.*.category' => 'nullable|string|max:100',
            'items.*.requires_cold_chain' => 'nullable|boolean',
            'seller_address' => 'required|string|min:5|max:500',
            'buyer_address' => 'required|string|min:5|max:500',
            'sub_vertical' => 'nullable|string|in:grocery_and_delivery,meat_shops,farm_direct,vegan_products,confectionery,food,office_catering',
        ]);

        try {
            $result = $this->supermarketService->prepareCheckout($validated);

            $this->logger->info('Checkout preparation successful', [
                'user_id' => $validated['user_id'],
                'correlation_id' => $result['correlation_id'] ?? null,
                'items_count' => count($validated['items']),
            ]);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\RuntimeException $e) {
            $this->logger->warning('Checkout preparation failed (business logic)', [
                'user_id' => $validated['user_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            $this->logger->error('Checkout preparation failed (unexpected)', [
                'user_id' => $validated['user_id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to prepare checkout',
            ], 500);
        }
    }

    /**
     * Создать заказ.
     */
    public function createOrder(Request $request): JsonResponse
    {
        // Authorization check
        Gate::authorize('createOrder', $request->user());

        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'items' => 'required|array|min:1|max:50',
            'items.*.product_id' => 'required|integer|min:1',
            'items.*.quantity' => 'required|integer|min:1|max:100',
            'items.*.price' => 'required|numeric|min:0|max:100000',
            'items.*.warehouse_id' => 'nullable|integer|min:1',
            'seller_address' => 'required|string|min:5|max:500',
            'buyer_address' => 'required|string|min:5|max:500',
            'delivery_slot' => 'required|string|min:5|max:50',
            'sub_vertical' => 'nullable|string|in:grocery_and_delivery,meat_shops,farm_direct,vegan_products,confectionery,food,office_catering',
            'warehouse_id' => 'nullable|integer|min:1',
            'cart_id' => 'nullable|integer|min:1',
            'business_group_id' => 'nullable|integer|min:1',
        ]);

        try {
            $result = $this->supermarketService->createOrder($validated);

            // Инвалидация кэша популярных товаров
            Cache::tags(['supermarket', 'products'])->flush();

            $this->logger->info('Order created successfully', [
                'order_id' => $result['order_id'],
                'user_id' => $validated['user_id'],
                'correlation_id' => $result['correlation_id'] ?? null,
                'reservation_count' => count($result['reservation_ids'] ?? []),
            ]);

            return response()->json([
                'success' => true,
                'data' => $result,
            ], 201);
        } catch (\RuntimeException $e) {
            $this->logger->warning('Order creation failed (business logic)', [
                'user_id' => $validated['user_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            $this->logger->error('Order creation failed (unexpected)', [
                'user_id' => $validated['user_id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create order',
            ], 500);
        }
    }

    /**
     * Получить список заказов пользователя.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $status = $request->query('status');
        $limit = (int) $request->query('limit', 20);

        $query = DB::table('supermarket_orders')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        if ($status) {
            $query->where('status', $status);
        }

        $orders = $query->get();

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Получить детали заказа.
     */
    public function show(Request $request, int $order): JsonResponse
    {
        $userId = $request->user()->id;

        $orderData = DB::table('supermarket_orders')
            ->where('id', $order)
            ->where('user_id', $userId)
            ->first();

        if (!$orderData) {
            return response()->json([
                'success' => false,
                'error' => 'Order not found',
            ], 404);
        }

        // Получить товары заказа
        $items = DB::table('inventory_reservations')
            ->join('inventory_items', 'inventory_reservations.inventory_id', '=', 'inventory_items.id')
            ->join('products', 'inventory_items.product_id', '=', 'products.id')
            ->where('inventory_reservations.order_id', $order)
            ->select([
                'products.id',
                'products.name',
                'products.price',
                'inventory_reservations.quantity',
                'products.category',
            ])
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'order' => $orderData,
                'items' => $items,
            ],
        ]);
    }

    /**
     * Отменить заказ.
     */
    public function cancelOrder(Request $request, int $order): JsonResponse
    {
        $userId = $request->user()->id;

        try {
            $result = $this->supermarketService->cancelOrder($order, $userId);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Подтвердить оплату.
     */
    public function confirmPayment(Request $request, int $order): JsonResponse
    {
        $validated = $request->validate([
            'payment_id' => 'required|string',
        ]);

        $userId = $request->user()->id;

        try {
            $result = $this->supermarketService->confirmPayment($order, $validated['payment_id'], $userId);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Создать резерв.
     */
    public function createReservation(Request $request): JsonResponse
    {
        // Authorization check
        Gate::authorize('createReservation', $request->user());

        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'items' => 'required|array|min:1|max:50',
            'items.*.product_id' => 'required|integer|min:1',
            'items.*.quantity' => 'required|integer|min:1|max:100',
            'items.*.warehouse_id' => 'nullable|integer|min:1',
            'cart_id' => 'nullable|integer|min:1',
            'business_group_id' => 'nullable|integer|min:1',
        ]);

        try {
            $result = $this->inventoryReservationService->reserveItems(
                userId: $validated['user_id'],
                items: $validated['items'],
                cartId: $validated['cart_id'] ?? null,
                orderId: null,
                businessGroupId: $validated['business_group_id'] ?? null,
            );

            $this->logger->info('Reservation created successfully', [
                'user_id' => $validated['user_id'],
                'reservation_count' => count($result['reservation_ids'] ?? []),
            ]);

            return response()->json([
                'success' => true,
                'data' => $result,
            ], 201);
        } catch (\RuntimeException $e) {
            $this->logger->warning('Reservation creation failed (business logic)', [
                'user_id' => $validated['user_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            $this->logger->error('Reservation creation failed (unexpected)', [
                'user_id' => $validated['user_id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create reservation',
            ], 500);
        }
    }

    /**
     * Продлить резерв (только B2B).
     */
    public function extendReservation(Request $request, int $reservation): JsonResponse
    {
        $validated = $request->validate([
            'additional_days' => 'nullable|integer|min:1|max:30',
        ]);

        $userId = $request->user()->id;

        try {
            $result = $this->inventoryReservationService->extendReservation(
                reservationId: $reservation,
                userId: $userId,
                additionalDays: $validated['additional_days'] ?? 7,
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to extend reservation',
            ], 500);
        }
    }

    /**
     * Освободить резерв.
     */
    public function releaseReservation(Request $request, int $reservation): JsonResponse
    {
        $userId = $request->user()->id;

        try {
            $this->inventoryReservationService->releaseReservations([$reservation], $userId);

            return response()->json([
                'success' => true,
                'message' => 'Reservation released',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить статус холодовой цепи.
     */
    public function getColdChainStatus(Request $request, int $order): JsonResponse
    {
        try {
            $status = $this->coldChainAdapter->getColdChainStatus($order);

            return response()->json([
                'success' => true,
                'data' => $status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить трекинг заказа.
     */
    public function getTracking(Request $request, int $order): JsonResponse
    {
        $userId = $request->user()->id;

        $orderData = DB::table('supermarket_orders')
            ->where('id', $order)
            ->where('user_id', $userId)
            ->first();

        if (!$orderData) {
            return response()->json([
                'success' => false,
                'error' => 'Order not found',
            ], 404);
        }

        // Здесь можно добавить интеграцию с RealtimeTrackingAdapter
        // Для простоты возвращаем базовую информацию
        return response()->json([
            'success' => true,
            'data' => [
                'order_id' => $order,
                'status' => $orderData->status,
                'delivery_eta' => $orderData->delivery_eta,
                'delivery_address' => $orderData->delivery_address,
                'delivery_slot' => $orderData->delivery_slot,
            ],
        ]);
    }

    /**
     * Освободить истекшие резервы (Admin).
     */
    public function releaseExpiredReservations(): JsonResponse
    {
        try {
            $releasedCount = $this->inventoryReservationService->releaseExpiredReservations();

            return response()->json([
                'success' => true,
                'data' => [
                    'released_count' => $releasedCount,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить статистику по резервам (Admin).
     */
    public function getReservationStats(): JsonResponse
    {
        try {
            $stats = [
                'total_reservations' => DB::table('inventory_reservations')->count(),
                'active_reservations' => DB::table('inventory_reservations')
                    ->where('expires_at', '>', now())
                    ->whereNull('order_id')
                    ->count(),
                'expired_reservations' => DB::table('inventory_reservations')
                    ->where('expires_at', '<', now())
                    ->whereNull('order_id')
                    ->count(),
                'b2b_reservations' => DB::table('inventory_reservations')
                    ->whereNotNull('business_group_id')
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get delivery slots for buyer checkout.
     */
    public function getDeliverySlots(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $tenantId = $request->user()?->tenant_id;

        try {
            $slots = $this->supermarketService->getCachedDeliverySlots(
                $request->input('address', ''),
                $request->input('sub_vertical')
            );

            return response()->json($slots);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process buyer checkout (One Page Checkout).
     */
    public function process(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'address' => 'required|string',
            'slot_id' => 'required|string',
            'payment_method' => 'required|string|in:card,sbp,tinkoff',
        ]);

        $userId = $request->user()->id;
        $tenantId = $request->user()?->tenant_id;

        try {
            // Get cart for user
            $cart = $this->supermarketService->getCart($userId, $tenantId);
            
            if (empty($cart['items'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cart is empty',
                ], 400);
            }

            // Prepare checkout data
            $checkoutData = [
                'user_id' => $userId,
                'items' => array_map(fn($item) => [
                    'product_id' => $item['product']['id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['product']['price'],
                    'warehouse_id' => 1,
                ], $cart['items']),
                'buyer_address' => $validated['address'],
                'seller_address' => config('verticals.supermarket.default_seller_address', 'Москва'),
                'delivery_slot' => $validated['slot_id'],
                'sub_vertical' => null,
                'tenant_id' => $tenantId,
            ];

            // Create order
            $result = $this->supermarketService->createOrder($checkoutData);

            // Clear cart after successful order
            $this->supermarketService->clearCart($userId, $tenantId);

            return response()->json([
                'success' => true,
                'order_uuid' => DB::table('supermarket_orders')
                    ->where('id', $result['order_id'])
                    ->value('uuid'),
                'total_amount' => $result['delivery_cost'] + $this->calculateCartTotal($cart['items']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function calculateCartTotal(array $items): int
    {
        return array_sum(array_map(fn($item) => $item['total'], $items));
    }
}
