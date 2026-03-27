<?php declare(strict_types=1);

namespace App\Domains\Confectionery\Services;

use App\Domains\Confectionery\Models\Cake;
use App\Domains\Confectionery\Models\CakeOrder;
use App\Domains\Confectionery\Models\CustomCakeDesign;
use App\Domains\Confectionery\Models\ConfectioneryShop;
use App\Services\FraudControlService;
use App\Services\InventoryManagementService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Carbon\Carbon;

final class ConfectioneryService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly InventoryManagementService $inventory,
        private readonly WalletService $wallet,
    ) {}

    /**
     * Создание заказа торта/выпечки (стандартный или кастомный).
     */
    public function createOrder(int $shopId, int $cakeId, array $data, string $correlationId = ""): CakeOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        if (RateLimiter::tooManyAttempts("cake:order:".auth()->id(), 5)) {
            throw new \RuntimeException("Too many orders", 429);
        }
        RateLimiter::hit("cake:order:".auth()->id(), 3600);

        return DB::transaction(function () use ($shopId, $cakeId, $data, $correlationId) {
            $shop = ConfectioneryShop::findOrFail($shopId);
            $cake = Cake::findOrFail($cakeId);

            $fraud = $this->fraud->check([
                'user_id' => auth()->id() ?? 0,
                'operation_type' => 'cake_order_create',
                'correlation_id' => $correlationId,
                'amount' => $cake->price_kopecks,
            ]);

            if ($fraud['decision'] === 'block') {
                Log::channel('audit')->error('Cake order blocked', [
                    'user_id' => auth()->id(),
                    'score' => $fraud['score'],
                    'correlation_id' => $correlationId,
                ]);
                throw new \RuntimeException("Security block", 403);
            }

            // Hold ингредиентов если нужно
            $this->inventory->reserveStock(
                itemId: $cakeId,
                quantity: 1,
                sourceType: 'cake_order',
                sourceId: 0
            );

            $order = CakeOrder::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $shop->tenant_id,
                'confectionery_shop_id' => $shopId,
                'user_id' => auth()->id(),
                'cake_id' => $cakeId,
                'status' => 'pending_payment',
                'total_price_kopecks' => $cake->price_kopecks,
                'delivery_date' => $data['delivery_date'] ?? now()->addDays(2),
                'message' => $data['message'] ?? '',
                'correlation_id' => $correlationId,
                'tags' => ['urgent:no', 'custom_design:no'],
            ]);

            Log::channel('audit')->info('Cake order created', [
                'order_id' => $order->id,
                'cake_id' => $cakeId,
                'user_id' => auth()->id(),
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Создание кастомного торта с дизайном.
     */
    public function createCustomCakeDesign(int $shopId, array $designData, string $correlationId = ""): CustomCakeDesign
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return DB::transaction(function () use ($shopId, $designData, $correlationId) {
            $shop = ConfectioneryShop::findOrFail($shopId);

            $design = CustomCakeDesign::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $shop->tenant_id,
                'confectionery_shop_id' => $shopId,
                'user_id' => auth()->id(),
                'description' => $designData['description'],
                'photo_url' => $designData['photo_url'] ?? null,
                'estimated_price_kopecks' => $designData['estimated_price'] ?? 50000,
                'estimated_time_hours' => $designData['estimated_time'] ?? 24,
                'status' => 'submitted',
                'correlation_id' => $correlationId,
                'tags' => ['custom:true', 'awaiting_approval:true'],
            ]);

            Log::channel('audit')->info('Custom cake design submitted', [
                'design_id' => $design->id,
                'user_id' => auth()->id(),
                'correlation_id' => $correlationId,
            ]);

            return $design;
        });
    }

    /**
     * Завершение заказа и выплата кондитерской.
     */
    public function completeOrder(int $orderId, string $correlationId = ""): void
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $order = CakeOrder::with('shop')->findOrFail($orderId);

        DB::transaction(function () use ($order, $correlationId) {
            if ($order->status !== 'ready') {
                throw new \RuntimeException("Order must be ready");
            }

            $order->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Списание ингредиентов
            $this->inventory->deductStock(
                itemId: $order->cake_id,
                quantity: 1,
                reason: "Cake order #{$order->id} completed",
                sourceType: 'cake_order',
                sourceId: $order->id
            );

            // Выплата (14% комиссия)
            $total = $order->total_price_kopecks;
            $platformFee = (int) ($total * 0.14);
            $payout = $total - $platformFee;

            $this->wallet->credit(
                userId: $order->shop->owner_id,
                amount: $payout,
                type: 'cake_order_payout',
                reason: "Cake order #{$order->id} completed",
                correlationId: $correlationId
            );

            Log::channel('audit')->info('Cake order completed', [
                'order_id' => $order->id,
                'payout_kopecks' => $payout,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Получение заказа.
     */
    public function getOrder(int $orderId): CakeOrder
    {
        return CakeOrder::findOrFail($orderId);
    }

    /**
     * Список заказов пользователя.
     */
    public function getUserOrders(int $userId, int $limit = 20): \Illuminate\Support\Collection
    {
        return CakeOrder::where('user_id', $userId)->orderBy('created_at', 'desc')->limit($limit)->get();
    }

    /**
     * Отметить как готово к доставке.
     */
    public function markReady(int $orderId, string $correlationId = ""): CakeOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $order = CakeOrder::findOrFail($orderId);

        return DB::transaction(function () use ($order, $correlationId) {
            if ($order->status !== 'in_production') {
                throw new \RuntimeException("Order not in production");
            }

            $order->update(['status' => 'ready']);

            Log::channel('audit')->info('Cake order marked ready', [
                'order_id' => $order->id,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }
}
