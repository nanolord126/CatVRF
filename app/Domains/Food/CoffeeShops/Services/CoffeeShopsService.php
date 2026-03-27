<?php declare(strict_types=1);

namespace App\Domains\Food\CoffeeShops\Services;

use App\Domains\Food\CoffeeShops\Models\CoffeeShop;
use App\Domains\Food\CoffeeShops\Models\CoffeeDrink;
use App\Domains\Food\CoffeeShops\Models\CoffeeOrder;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class CoffeeShopsService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly WalletService $wallet,
    ) {}

    public function createOrder(int $shopId, array $items, array $data, string $correlationId = ""): CoffeeOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        if (RateLimiter::tooManyAttempts("coffee:order:".auth()->id(), 20)) {
            throw new \RuntimeException("Too many orders", 429);
        }
        RateLimiter::hit("coffee:order:".auth()->id(), 3600);

        return DB::transaction(function () use ($shopId, $items, $data, $correlationId) {
            $shop = CoffeeShop::findOrFail($shopId);
            $total = 0;

            foreach ($items as $item) {
                $drink = CoffeeDrink::where('id', $item['drink_id'])
                    ->where('shop_id', $shopId)->firstOrFail();
                $total += $drink->price_kopecks * $item['quantity'];
            }

            $fraud = $this->fraud->check([
                'user_id' => auth()->id() ?? 0,
                'operation_type' => 'coffee_order_create',
                'correlation_id' => $correlationId,
                'amount' => $total,
            ]);

            if ($fraud['decision'] === 'block') {
                Log::channel('audit')->error('Coffee order blocked', [
                    'user_id' => auth()->id(),
                    'score' => $fraud['score'],
                    'correlation_id' => $correlationId,
                ]);
                throw new \RuntimeException("Security block", 403);
            }

            $order = CoffeeOrder::create([
                'uuid' => Str::uuid(),
                'tenant_id' => tenant()->id,
                'shop_id' => $shopId,
                'client_id' => auth()->id() ?? 0,
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'total_kopecks' => $total,
                'payout_kopecks' => $total - (int) ($total * 0.14),
                'payment_status' => 'pending',
                'items_json' => $items,
                'delivery_type' => $data['delivery_type'] ?? 'pickup',
                'tags' => ['coffee' => true, 'items_count' => count($items)],
            ]);

            Log::channel('audit')->info('Coffee order created', [
                'order_id' => $order->id,
                'shop_id' => $shopId,
                'total_kopecks' => $total,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    public function completeOrder(int $orderId, string $correlationId = ""): CoffeeOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return DB::transaction(function () use ($orderId, $correlationId) {
            $order = CoffeeOrder::findOrFail($orderId);

            if ($order->payment_status !== 'completed') {
                throw new \RuntimeException("Order not paid", 400);
            }

            $order->update(['status' => 'completed', 'correlation_id' => $correlationId]);

            $this->wallet->credit(tenant()->id, $order->payout_kopecks, 'coffee_payout', [
                'correlation_id' => $correlationId,
                'order_id' => $order->id,
            ]);

            Log::channel('audit')->info('Coffee order completed', [
                'order_id' => $order->id,
                'payout_kopecks' => $order->payout_kopecks,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    public function cancelOrder(int $orderId, string $correlationId = ""): CoffeeOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return DB::transaction(function () use ($orderId, $correlationId) {
            $order = CoffeeOrder::findOrFail($orderId);

            if ($order->status === 'completed') {
                throw new \RuntimeException("Cannot cancel completed", 400);
            }

            $order->update(['status' => 'cancelled', 'payment_status' => 'refunded', 'correlation_id' => $correlationId]);

            if ($order->payment_status === 'completed') {
                $this->wallet->credit(tenant()->id, $order->total_kopecks, 'coffee_refund', [
                    'correlation_id' => $correlationId,
                    'order_id' => $order->id,
                ]);
            }

            Log::channel('audit')->info('Coffee order cancelled', [
                'order_id' => $order->id,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    public function getOrder(int $orderId): CoffeeOrder
    {
        return CoffeeOrder::with(['shop'])->findOrFail($orderId);
    }

    public function getUserOrders(int $clientId)
    {
        return CoffeeOrder::where('client_id', $clientId)->orderBy('created_at', 'desc')->take(10)->get();
    }

    public function getShopDrinks(int $shopId)
    {
        return CoffeeDrink::where('shop_id', $shopId)->orderBy('name')->get();
    }
}
