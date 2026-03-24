<?php declare(strict_types=1);

namespace App\Domains\Cosmetics\Services;

use App\Domains\Cosmetics\Models\CosmeticProduct;
use App\Domains\Cosmetics\Models\CosmeticOrder;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class CosmeticsService
{
    public function __construct(private readonly FraudControlService $fraud, private readonly WalletService $wallet) {}

    public function createOrder(int $sellerId, array $items, string $correlationId = ""): CosmeticOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        if (RateLimiter::tooManyAttempts("cosmetics:order:".auth()->id(), 15)) throw new \RuntimeException("Too many orders", 429);
        RateLimiter::hit("cosmetics:order:".auth()->id(), 3600);

        return DB::transaction(function () use ($sellerId, $items, $correlationId) {
            $total = 0;
            foreach ($items as $item) {
                $product = CosmeticProduct::where('id', $item['product_id'])->firstOrFail();
                $total += $product->price_kopecks * $item['quantity'];
                if ($product->stock < $item['quantity']) throw new \RuntimeException("Out of stock", 400);
            }

            $fraud = $this->fraud->check(['user_id' => auth()->id() ?? 0, 'operation_type' => 'cosmetic_order', 'correlation_id' => $correlationId, 'amount' => $total]);
            if ($fraud['decision'] === 'block') {
                Log::channel('audit')->error('Cosmetic order blocked', ['user_id' => auth()->id(), 'correlation_id' => $correlationId]);
                throw new \RuntimeException("Security block", 403);
            }

            $order = CosmeticOrder::create([
                'uuid' => Str::uuid(), 'tenant_id' => tenant()->id, 'seller_id' => $sellerId, 'client_id' => auth()->id() ?? 0,
                'correlation_id' => $correlationId, 'status' => 'pending_payment', 'total_kopecks' => $total,
                'payout_kopecks' => $total - (int) ($total * 0.14), 'payment_status' => 'pending', 'items_json' => $items,
                'tags' => ['cosmetics' => true],
            ]);

            Log::channel('audit')->info('Cosmetic order created', ['order_id' => $order->id, 'correlation_id' => $correlationId]);
            return $order;
        });
    }

    public function completeOrder(int $orderId, string $correlationId = ""): CosmeticOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        return DB::transaction(function () use ($orderId, $correlationId) {
            $order = CosmeticOrder::findOrFail($orderId);
            if ($order->payment_status !== 'completed') throw new \RuntimeException("Order not paid", 400);
            foreach ($order->items_json as $item) {
                CosmeticProduct::findOrFail($item['product_id'])->decrement('stock', $item['quantity']);
            }
            $order->update(['status' => 'completed', 'correlation_id' => $correlationId]);
            $this->wallet->credit(tenant()->id, $order->payout_kopecks, 'cosmetic_payout', ['correlation_id' => $correlationId, 'order_id' => $order->id]);
            Log::channel('audit')->info('Cosmetic order completed', ['order_id' => $order->id, 'correlation_id' => $correlationId]);
            return $order;
        });
    }

    public function cancelOrder(int $orderId, string $correlationId = ""): CosmeticOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        return DB::transaction(function () use ($orderId, $correlationId) {
            $order = CosmeticOrder::findOrFail($orderId);
            if ($order->status === 'completed') throw new \RuntimeException("Cannot cancel completed", 400);
            $order->update(['status' => 'cancelled', 'payment_status' => 'refunded', 'correlation_id' => $correlationId]);
            if ($order->payment_status === 'completed') {
                $this->wallet->credit(tenant()->id, $order->total_kopecks, 'cosmetic_refund', ['correlation_id' => $correlationId, 'order_id' => $order->id]);
            }
            Log::channel('audit')->info('Cosmetic order cancelled', ['order_id' => $order->id, 'correlation_id' => $correlationId]);
            return $order;
        });
    }

    public function getOrder(int $orderId): CosmeticOrder { return CosmeticOrder::findOrFail($orderId); }
    public function getUserOrders(int $clientId) { return CosmeticOrder::where('client_id', $clientId)->orderBy('created_at', 'desc')->take(10)->get(); }
}
