<?php declare(strict_types=1);

namespace App\Domains\MeatShops\Services;

use App\Domains\MeatShops\Models\MeatShop;
use App\Domains\MeatShops\Models\MeatOrder;
use App\Domains\MeatShops\Models\MeatBoxSubscription;
use App\Services\FraudControlService;
use App\Services\InventoryManagementService;
use App\Services\WalletService;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Carbon\Carbon;

final class MeatShopService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly InventoryManagementService $inventory,
        private readonly WalletService $wallet,
        private readonly PaymentService $payment,
    ) {}

    /**
     * Создание заказа мяса (коробка свежего мяса).
     */
    public function createOrder(int $meatShopId, array $items, string $correlationId = ""): MeatOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        // Rate Limiting - защита от ботов на дефицитное мясо
        if (RateLimiter::tooManyAttempts("meat:order:".auth()->id(), 10)) {
            throw new \RuntimeException("Too many orders. Please wait.", 429);
        }
        RateLimiter::hit("meat:order:".auth()->id(), 3600);

        return $this->db->transaction(function () use ($meatShopId, $items, $correlationId) {
            $shop = MeatShop::findOrFail($meatShopId);

            // Fraud Check
            $fraud = $this->fraud->check([
                'user_id' => auth()->id() ?? 0,
                'operation_type' => 'meat_order_create',
                'correlation_id' => $correlationId,
                'amount' => collect($items)->sum('price'),
            ]);

            if ($fraud['decision'] === 'block') {
                $this->log->channel('audit')->error('Meat order security block', [
                    'user_id' => auth()->id(),
                    'score' => $fraud['score'],
                    'correlation_id' => $correlationId,
                ]);
                throw new \RuntimeException("Blocked by security system", 403);
            }

            $totalPrice = 0;

            // Резервация мяса в Inventory
            foreach ($items as $item) {
                $totalPrice += ($item['price'] * $item['quantity']);
                
                $this->inventory->reserveStock(
                    itemId: $item['product_id'],
                    quantity: $item['quantity'],
                    sourceType: 'meat_order',
                    sourceId: 0
                );
            }

            // Создание заказа
            $order = MeatOrder::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $shop->tenant_id,
                'meat_shop_id' => $meatShopId,
                'user_id' => auth()->id(),
                'status' => 'pending_payment',
                'total_price_kopecks' => $totalPrice,
                'items_json' => $items,
                'delivery_address' => auth()->user()->address ?? '',
                'correlation_id' => $correlationId,
                'tags' => ['cold_chain:yes', 'fresh_meat:true'],
            ]);

            $this->log->channel('audit')->info('Meat order created', [
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'total' => $totalPrice,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Создание подписки на мясной бокс.
     */
    public function createSubscription(int $meatShopId, array $data, string $correlationId = ""): MeatBoxSubscription
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($meatShopId, $data, $correlationId) {
            $shop = MeatShop::findOrFail($meatShopId);

            // Fraud Check - проверка частоты подписок одного пользователя
            $fraud = $this->fraud->check([
                'user_id' => auth()->id(),
                'operation_type' => 'meat_subscription_create',
                'correlation_id' => $correlationId,
                'amount' => $data['price_kopecks'],
            ]);

            if ($fraud['decision'] === 'block') {
                throw new \RuntimeException("Subscription blocked by fraud check", 403);
            }

            $subscription = MeatBoxSubscription::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $shop->tenant_id,
                'meat_shop_id' => $meatShopId,
                'user_id' => auth()->id(),
                'name' => $data['name'],
                'price_kopecks' => $data['price_kopecks'],
                'frequency' => $data['frequency'] ?? 'weekly', // weekly, bi-weekly, monthly
                'meat_types' => $data['meat_types'] ?? ['beef', 'pork'],
                'total_weight_grams' => $data['total_weight_grams'],
                'delivery_day' => $data['delivery_day'] ?? 'monday',
                'is_active' => true,
                'started_at' => now(),
                'correlation_id' => $correlationId,
                'tags' => ['subscription:active', 'auto_renewal:true'],
            ]);

            $this->log->channel('audit')->info('Meat subscription created', [
                'subscription_id' => $subscription->id,
                'user_id' => auth()->id(),
                'frequency' => $data['frequency'],
                'correlation_id' => $correlationId,
            ]);

            return $subscription;
        });
    }

    /**
     * Завершение заказа и выплата мясо-лавке.
     */
    public function completeOrder(int $orderId, string $correlationId = ""): void
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $order = MeatOrder::with('meatShop')->findOrFail($orderId);

        $this->db->transaction(function () use ($order, $correlationId) {
            if ($order->status !== 'paid') {
                throw new \RuntimeException("Order must be paid before completing");
            }

            $order->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Списание мяса из Inventory
            $this->inventory->deductStock(
                itemId: 0,
                quantity: 1,
                reason: "Meat order completed: {$order->id}",
                sourceType: 'meat_order',
                sourceId: $order->id
            );

            // Расчет выплаты (14% комиссия платформы, 12% если мигрантcя)
            $total = $order->total_price_kopecks;
            $platformFee = (int) ($total * 0.14);
            $payout = $total - $platformFee;

            // Выплата мясо-лавке
            $this->wallet->credit(
                userId: $order->meatShop->owner_id,
                amount: $payout,
                type: 'meat_order_payout',
                reason: "Meat order #{$order->id} delivered",
                correlationId: $correlationId
            );

            $this->log->channel('audit')->info('Meat order completed and payout processed', [
                'order_id' => $order->id,
                'payout_kopecks' => $payout,
                'commission' => $platformFee,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Получение заказа.
     */
    public function getOrder(int $orderId): MeatOrder
    {
        return MeatOrder::findOrFail($orderId);
    }

    /**
     * Список заказов пользователя.
     */
    public function getUserOrders(int $userId, int $limit = 20): \Illuminate\Support\Collection
    {
        return MeatOrder::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Список заказов лавки.
     */
    public function getShopOrders(int $shopId, int $limit = 50): \Illuminate\Support\Collection
    {
        return MeatOrder::where('meat_shop_id', $shopId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
