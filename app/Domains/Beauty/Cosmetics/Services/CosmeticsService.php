<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Cosmetics\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;

final readonly class CosmeticsService
{
    public function __construct(
        private FraudControlService $fraud,
        private WalletService $wallet,
        private \Illuminate\Database\DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
        private \Illuminate\Cache\RateLimiter $rateLimiter,
    ) {}

        public function createOrder(int $sellerId, array $items, string $correlationId = ""): CosmeticOrder
        {
            $correlationId = $correlationId ?: (string) Str::uuid();
            if ($this->rateLimiter->tooManyAttempts("cosmetics:order:".$this->guard->id(), 15)) throw new \RuntimeException("Too many orders", 429);
            $this->rateLimiter->hit("cosmetics:order:".$this->guard->id(), 3600);

            return $this->db->transaction(function () use ($sellerId, $items, $correlationId) {
                $total = 0;
                foreach ($items as $item) {
                    $product = CosmeticProduct::where('id', $item['product_id'])->firstOrFail();
                    $total += $product->price_kopecks * $item['quantity'];
                    if ($product->stock < $item['quantity']) throw new \RuntimeException("Out of stock", 400);
                }

                $fraudResult = $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'cosmetic_order', amount: 0, correlationId: $correlationId ?? '');
                if ($fraudResult['decision'] === 'block') {
                    $this->logger->error('Cosmetic order blocked', ['user_id' => $this->guard->id(), 'correlation_id' => $correlationId]);
                    throw new \RuntimeException("Security block", 403);
                }

                $order = CosmeticOrder::create([
                    'uuid' => Str::uuid(), 'tenant_id' => tenant()->id, 'seller_id' => $sellerId, 'client_id' => $this->guard->id() ?? 0,
                    'correlation_id' => $correlationId, 'status' => 'pending_payment', 'total_kopecks' => $total,
                    'payout_kopecks' => $total - (int) ($total * 0.14), 'payment_status' => 'pending', 'items_json' => $items,
                    'tags' => ['cosmetics' => true],
                ]);

                $this->logger->info('Cosmetic order created', ['order_id' => $order->id, 'correlation_id' => $correlationId]);
                return $order;
            });
        }

        public function completeOrder(int $orderId, string $correlationId = ""): CosmeticOrder
        {
            $correlationId = $correlationId ?: (string) Str::uuid();
            return $this->db->transaction(function () use ($orderId, $correlationId) {
                $order = CosmeticOrder::findOrFail($orderId);
                if ($order->payment_status !== 'completed') throw new \RuntimeException("Order not paid", 400);
                foreach ($order->items_json as $item) {
                    CosmeticProduct::findOrFail($item['product_id'])->decrement('stock', $item['quantity']);
                }
                $order->update(['status' => 'completed', 'correlation_id' => $correlationId]);
                $this->wallet->credit(tenant()->id, $order->payout_kopecks, \App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT, $correlationId, null, null, ['order_id' => $order->id, 'correlation_id' => $correlationId]);
                return $order;
            });
        }

        public function cancelOrder(int $orderId, string $correlationId = ""): CosmeticOrder
        {
            $correlationId = $correlationId ?: (string) Str::uuid();
            return $this->db->transaction(function () use ($orderId, $correlationId) {
                $order = CosmeticOrder::findOrFail($orderId);
                if ($order->status === 'completed') throw new \RuntimeException("Cannot cancel completed", 400);
                $order->update(['status' => 'cancelled', 'payment_status' => 'refunded', 'correlation_id' => $correlationId]);
                if ($order->payment_status === 'completed') {
                    $this->wallet->credit(tenant()->id, $order->total_kopecks, \App\Domains\Wallet\Enums\BalanceTransactionType::REFUND, $correlationId, null, null, ['order_id' => $order->id, 'correlation_id' => $correlationId]);
                }
                return $order;
            });
        }

        public function getOrder(int $orderId): CosmeticOrder { return CosmeticOrder::findOrFail($orderId); }
        public function getUserOrders(int $clientId) { return CosmeticOrder::where('client_id', $clientId)->orderBy('created_at', 'desc')->take(10)->get(); }
}
