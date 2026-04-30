<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Supermarket;

use App\Domains\Supermarket\Services\SellerAnalyticsService;
use App\Domains\Supermarket\Services\SubscriptionService;
use App\Domains\Supermarket\Services\SubscriptionNotificationService;
use App\Domains\Supermarket\Models\SupermarketOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Buyer Dashboard Controller
 * 
 * Provides comprehensive buyer dashboard data including:
 * - Home tab: Overview, quick actions, recommendations
 * - Orders tab: Order history, status tracking, reordering
 * - Subscriptions tab: Active subscriptions, management actions
 * - Profile tab: User settings, addresses, payment methods
 * 
 * @version 2026.1
 */
final readonly class BuyerDashboardController
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private SubscriptionNotificationService $notificationService
    ) {
    }

    /**
     * Home tab - Overview with quick actions and recommendations
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $cacheKey = "buyer_dashboard:home:{$user->id}";

        $data = Cache::remember($cacheKey, 300, function () use ($user) {
            $subscriptions = $user->subscriptions()
                ->with(['items.product', 'seller'])
                ->where('status', 'active')
                ->get();

            $lastOrder = $user->supermarketOrders()
                ->with(['items.product'])
                ->orderBy('created_at', 'desc')
                ->first();

            $pendingOrders = $user->supermarketOrders()
                ->whereIn('status', ['pending', 'processing', 'picked'])
                ->count();

            $activeOrders = $user->supermarketOrders()
                ->whereIn('status', ['in_delivery', 'out_for_delivery'])
                ->count();

            return [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'bonus_balance' => $user->bonus_balance ?? 0,
                    'age_verified' => $user->age_verified_at !== null,
                    'telegram_id' => $user->telegram_id ? true : false,
                    'whatsapp_phone' => $user->whatsapp_phone ? true : false,
                    'avatar_url' => $user->avatar_url ?? null,
                ],
                'stats' => [
                    'active_subscriptions_count' => $subscriptions->count(),
                    'pending_orders_count' => $pendingOrders,
                    'active_deliveries_count' => $activeOrders,
                    'total_orders_this_month' => $user->supermarketOrders()
                        ->whereMonth('created_at', now()->month)
                        ->count(),
                ],
                'active_subscriptions' => $subscriptions->map(function ($sub) {
                    return [
                        'id' => $sub->id,
                        'frequency' => $sub->frequency,
                        'next_delivery_at' => $sub->next_delivery_at->toIso8601String(),
                        'total_amount' => $sub->total_amount,
                        'items_count' => $sub->items->count(),
                        'seller_name' => $sub->seller->shop_name ?? 'Магазин',
                        'can_pause' => true,
                        'can_skip' => true,
                    ];
                }),
                'last_order' => $lastOrder ? [
                    'id' => $lastOrder->id,
                    'uuid' => $lastOrder->uuid,
                    'total_amount' => $lastOrder->total_amount,
                    'status' => $lastOrder->status,
                    'status_label' => $lastOrder->getStatusLabel(),
                    'created_at' => $lastOrder->created_at->toIso8601String(),
                    'items_count' => $lastOrder->items->count(),
                    'estimated_delivery' => $lastOrder->estimated_delivery_at?->toIso8601String(),
                ] : null,
                'quick_actions' => [
                    'can_create_subscription' => true,
                    'can_repeat_order' => $lastOrder !== null,
                    'has_pending_delivery' => $activeOrders > 0,
                    'has_unpaid_orders' => $user->supermarketOrders()
                        ->where('status', 'pending_payment')
                        ->exists(),
                ],
                'promotions' => [
                    [
                        'id' => 'welcome_bonus',
                        'title' => 'Приветственный бонус',
                        'description' => 'Сделайте первый заказ и получите 500 бонусов',
                        'badge' => 'NEW',
                        'action_url' => '/supermarket/catalog',
                    ],
                ],
            ];
        });

        return response()->json($data);
    }

    /**
     * Orders tab - Order history with filtering and status
     */
    public function orders(Request $request): JsonResponse
    {
        $user = $request->user();
        $status = $request->query('status', 'all');
        $limit = min($request->query('limit', 20), 50);
        $offset = $request->query('offset', 0);

        $query = $user->supermarketOrders()
            ->with(['items.product', 'seller', 'deliveryAddress']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $total = $query->count();

        return response()->json([
            'data' => $orders->map(function (SupermarketOrder $order) {
                return [
                    'id' => $order->id,
                    'uuid' => $order->uuid,
                    'order_number' => $order->order_number,
                    'total_amount' => $order->total_amount,
                    'status' => $order->status,
                    'status_label' => $order->getStatusLabel(),
                    'status_color' => $order->getStatusColor(),
                    'created_at' => $order->created_at->toIso8601String(),
                    'estimated_delivery_at' => $order->estimated_delivery_at?->toIso8601String(),
                    'delivered_at' => $order->delivered_at?->toIso8601String(),
                    'items_count' => $order->items->count(),
                    'items_preview' => $order->items->take(3)->map(function ($item) {
                        return [
                            'product_name' => $item->product->name,
                            'quantity' => $item->quantity,
                        ];
                    }),
                    'seller_name' => $order->seller->shop_name ?? 'Магазин',
                    'delivery_address' => $order->deliveryAddress ? [
                        'city' => $order->deliveryAddress->city,
                        'street' => $order->deliveryAddress->street,
                        'house' => $order->deliveryAddress->house,
                    ] : null,
                    'can_track' => in_array($order->status, ['picked', 'in_delivery', 'out_for_delivery']),
                    'can_repeat' => true,
                    'can_return' => in_array($order->status, ['delivered']),
                ];
            }),
            'meta' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => $total > ($offset + $limit),
            ],
            'status_options' => [
                'all' => 'Все',
                'pending' => 'Ожидает',
                'processing' => 'В обработке',
                'picked' => 'Собран',
                'in_delivery' => 'В доставке',
                'delivered' => 'Доставлен',
                'cancelled' => 'Отменён',
            ],
        ]);
    }

    /**
     * Single order detail
     */
    public function order(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user();
        
        $order = $user->supermarketOrders()
            ->with(['items.product', 'seller', 'deliveryAddress', 'payment'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        return response()->json([
            'data' => [
                'id' => $order->id,
                'uuid' => $order->uuid,
                'order_number' => $order->order_number,
                'total_amount' => $order->total_amount,
                'subtotal' => $order->subtotal,
                'delivery_fee' => $order->delivery_fee,
                'discount_amount' => $order->discount_amount,
                'bonus_used' => $order->bonus_used,
                'status' => $order->status,
                'status_label' => $order->getStatusLabel(),
                'status_color' => $order->getStatusColor(),
                'created_at' => $order->created_at->toIso8601String(),
                'updated_at' => $order->updated_at->toIso8601String(),
                'estimated_delivery_at' => $order->estimated_delivery_at?->toIso8601String(),
                'delivered_at' => $order->delivered_at?->toIso8601String(),
                'cancelled_at' => $order->cancelled_at?->toIso8601String(),
                'is_b2b' => $order->is_b2b,
                'payment_method' => $order->payment_method,
                'seller' => [
                    'id' => $order->seller->id,
                    'shop_name' => $order->seller->shop_name,
                    'logo_url' => $order->seller->logo_url ?? null,
                ],
                'delivery_address' => $order->deliveryAddress ? [
                    'id' => $order->deliveryAddress->id,
                    'city' => $order->deliveryAddress->city,
                    'street' => $order->deliveryAddress->street,
                    'house' => $order->deliveryAddress->house,
                    'apartment' => $order->deliveryAddress->apartment,
                    'entrance' => $order->deliveryAddress->entrance,
                    'floor' => $order->deliveryAddress->floor,
                    'intercom' => $order->deliveryAddress->intercom,
                    'comment' => $order->deliveryAddress->comment,
                ] : null,
                'items' => $order->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name,
                        'product_image' => $item->product->image_url ?? null,
                        'quantity' => $item->quantity,
                        'price_per_unit' => $item->price_per_unit_at_creation,
                        'total_price' => $item->total_price_at_creation,
                        'weight' => $item->weight_at_creation,
                    ];
                }),
                'timeline' => $this->getOrderTimeline($order),
                'actions' => [
                    'can_track' => in_array($order->status, ['picked', 'in_delivery', 'out_for_delivery']),
                    'can_repeat' => true,
                    'can_return' => in_array($order->status, ['delivered']),
                    'can_cancel' => in_array($order->status, ['pending', 'processing']),
                ],
            ],
        ]);
    }

    /**
     * Subscriptions tab - List and manage subscriptions
     */
    public function subscriptions(Request $request): JsonResponse
    {
        $user = $request->user();
        $status = $request->query('status', 'all');

        $query = $user->subscriptions()
            ->with(['items.product', 'seller']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $subscriptions = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $subscriptions->map(function ($sub) {
                return [
                    'id' => $sub->id,
                    'frequency' => $sub->frequency,
                    'frequency_label' => $sub->getFrequencyLabel(),
                    'delivery_day' => $sub->delivery_day,
                    'delivery_time_slot' => $sub->delivery_time_slot,
                    'next_delivery_at' => $sub->next_delivery_at->toIso8601String(),
                    'total_amount' => $sub->total_amount,
                    'status' => $sub->status,
                    'status_label' => $sub->getStatusLabel(),
                    'status_color' => $sub->getStatusColor(),
                    'pause_until' => $sub->pause_until?->toIso8601String(),
                    'created_at' => $sub->created_at->toIso8601String(),
                    'items' => $sub->items->map(function ($item) {
                        return [
                            'product_id' => $item->product_id,
                            'product_name' => $item->product->name,
                            'product_image' => $item->product->image_url ?? null,
                            'quantity' => $item->quantity,
                            'price' => $item->price_per_unit_at_creation,
                        ];
                    }),
                    'seller_name' => $sub->seller->shop_name ?? 'Магазин',
                    'seller_logo' => $sub->seller->logo_url ?? null,
                    'delivery_count' => $sub->delivery_count,
                    'can_pause' => $sub->status === 'active',
                    'can_skip' => $sub->status === 'active',
                    'can_cancel' => $sub->status === 'active' || $sub->status === 'paused',
                    'can_edit' => $sub->status === 'active',
                ];
            }),
            'status_options' => [
                'all' => 'Все',
                'active' => 'Активные',
                'paused' => 'Приостановлены',
                'cancelled' => 'Отменены',
            ],
        ]);
    }

    /**
     * Profile tab - User settings and preferences
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar_url' => $user->avatar_url ?? null,
                'bonus_balance' => $user->bonus_balance ?? 0,
                'age_verified' => $user->age_verified_at !== null,
                'age_verified_at' => $user->age_verified_at?->toIso8601String(),
                'created_at' => $user->created_at->toIso8601String(),
            ],
            'notifications' => [
                'telegram_enabled' => $user->telegram_id !== null,
                'whatsapp_enabled' => $user->whatsapp_phone !== null,
                'email_enabled' => true,
                'push_enabled' => true,
            ],
            'addresses' => $user->addresses()->orderBy('is_default', 'desc')->get()->map(function ($address) {
                return [
                    'id' => $address->id,
                    'city' => $address->city,
                    'street' => $address->street,
                    'house' => $address->house,
                    'apartment' => $address->apartment,
                    'entrance' => $address->entrance,
                    'floor' => $address->floor,
                    'intercom' => $address->intercom,
                    'comment' => $address->comment,
                    'is_default' => $address->is_default,
                ];
            }),
            'payment_methods' => [
                'cards' => [], // TODO: Implement payment methods
                'has_saved_cards' => false,
            ],
            'preferences' => [
                'default_delivery_time_slot' => $user->preferences['default_delivery_time_slot'] ?? null,
                'default_frequency' => $user->preferences['default_frequency'] ?? 'weekly',
                'skip_weekends' => $user->preferences['skip_weekends'] ?? false,
            ],
        ];

        return response()->json($data);
    }

    /**
     * Pause subscription
     */
    public function pauseSubscription(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $subscription = $user->subscriptions()->findOrFail($id);

        $days = $request->input('days', 7);
        $this->subscriptionService->pause($subscription, $days);
        $this->notificationService->sendSubscriptionPaused($subscription, "User request - {$days} days");

        return response()->json(['message' => 'Subscription paused']);
    }

    /**
     * Resume subscription
     */
    public function resumeSubscription(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $subscription = $user->subscriptions()->findOrFail($id);

        $this->subscriptionService->resume($subscription);

        return response()->json(['message' => 'Subscription resumed']);
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $subscription = $user->subscriptions()->findOrFail($id);

        $reason = $request->input('reason', 'User request');
        $this->subscriptionService->cancel($subscription);
        $this->notificationService->sendSubscriptionCancelled($subscription);

        return response()->json(['message' => 'Subscription cancelled']);
    }

    /**
     * Skip next delivery
     */
    public function skipNextDelivery(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $subscription = $user->subscriptions()->findOrFail($id);

        $this->subscriptionService->skipNextDelivery($subscription);

        return response()->json(['message' => 'Next delivery skipped']);
    }

    /**
     * Get order timeline for tracking
     */
    private function getOrderTimeline(SupermarketOrder $order): array
    {
        $timeline = [
            [
                'status' => 'created',
                'label' => 'Заказ создан',
                'timestamp' => $order->created_at->toIso8601String(),
                'completed' => true,
            ],
        ];

        if ($order->confirmed_at) {
            $timeline[] = [
                'status' => 'confirmed',
                'label' => 'Подтверждён',
                'timestamp' => $order->confirmed_at->toIso8601String(),
                'completed' => true,
            ];
        }

        if ($order->picked_at) {
            $timeline[] = [
                'status' => 'picked',
                'label' => 'Собран',
                'timestamp' => $order->picked_at->toIso8601String(),
                'completed' => true,
            ];
        }

        if ($order->in_delivery_at) {
            $timeline[] = [
                'status' => 'in_delivery',
                'label' => 'В доставке',
                'timestamp' => $order->in_delivery_at->toIso8601String(),
                'completed' => in_array($order->status, ['in_delivery', 'out_for_delivery', 'delivered']),
            ];
        }

        if ($order->delivered_at) {
            $timeline[] = [
                'status' => 'delivered',
                'label' => 'Доставлен',
                'timestamp' => $order->delivered_at->toIso8601String(),
                'completed' => $order->status === 'delivered',
            ];
        }

        if ($order->cancelled_at) {
            $timeline[] = [
                'status' => 'cancelled',
                'label' => 'Отменён',
                'timestamp' => $order->cancelled_at->toIso8601String(),
                'completed' => $order->status === 'cancelled',
            ];
        }

        return $timeline;
    }
}
