<?php declare(strict_types=1);

namespace App\Domains\ToysAndGames\ToysKids\Services;

use App\Domains\ToysAndGames\ToysKids\Models\ToyOrder;
use App\Domains\ToysAndGames\ToysKids\Models\ToyProduct;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use RuntimeException;

final readonly class ToyOrderService
{
    public function __construct(
        private readonly \App\Services\FraudControlService $fraud,
        private readonly \App\Domains\Wallet\Services\WalletService $wallet,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard,
    ) {}

    /**
     * Создание заказа на игрушку с проверкой наличия и сертификата безопасности.
     */
    public function createOrder(
        int $productId,
        int $quantity,
        bool $giftWrapping,
        string $deliveryDate,
        int $tenantId,
        string $correlationId,
    ): ToyOrder {
        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'toy_order_create',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($productId, $quantity, $giftWrapping, $deliveryDate, $tenantId, $correlationId): ToyOrder {
            $product = ToyProduct::lockForUpdate()->findOrFail($productId);

            if ($product->current_stock < $quantity) {
                throw new RuntimeException("Insufficient stock for {$product->name}. Available: {$product->current_stock}.");
            }

            if (!$product->has_safety_certificate) {
                throw new RuntimeException("Product {$product->name} lacks safety certificate and cannot be sold.");
            }

            $unitPrice = $product->price;
            $wrappingFee = $giftWrapping && $product->gift_wrapping_available ? 5000 : 0;
            $totalPrice = ($unitPrice * $quantity) + $wrappingFee;

            $product->decrement('current_stock', $quantity);

            $order = ToyOrder::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
                'product_id' => $productId,
                'client_id' => $this->guard->id(),
                'quantity' => $quantity,
                'gift_wrapping' => $giftWrapping,
                'total_price' => $totalPrice,
                'delivery_date' => $deliveryDate,
                'status' => 'pending',
                'idempotency_key' => md5("{$this->guard->id()}:{$productId}:{$quantity}:{$deliveryDate}"),
                'tags' => [
                    'gift_wrapping' => $giftWrapping,
                    'age_range' => "{$product->age_min_years}-{$product->age_max_years}",
                ],
            ]);

            $this->logger->info('Toy order created', [
                'order_id' => $order->id,
                'order_uuid' => $order->uuid,
                'product_id' => $productId,
                'quantity' => $quantity,
                'total_price' => $totalPrice,
                'gift_wrapping' => $giftWrapping,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Подтверждение доставки и выплата продавцу.
     */
    public function confirmDelivery(int $orderId, string $correlationId): ToyOrder
    {
        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'toy_order_deliver',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($orderId, $correlationId): ToyOrder {
            $order = ToyOrder::with('product')->lockForUpdate()->findOrFail($orderId);

            if (!$order->isPending()) {
                throw new RuntimeException("Order {$orderId} is not in pending state.");
            }

            $platformFee = (int) ($order->total_price * 0.14);
            $payoutAmount = $order->total_price - $platformFee;

            $order->update([
                'status' => 'delivered',
                'delivered_at' => now(),
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Toy order delivered', [
                'order_id' => $order->id,
                'payout_amount' => $payoutAmount,
                'platform_fee' => $platformFee,
                'correlation_id' => $correlationId,
            ]);

            return $order->refresh();
        });
    }

    /**
     * Отмена заказа с возвратом товара на склад.
     */
    public function cancelOrder(int $orderId, string $reason, string $correlationId): ToyOrder
    {
        return $this->db->transaction(function () use ($orderId, $reason, $correlationId): ToyOrder {
            $order = ToyOrder::with('product')->lockForUpdate()->findOrFail($orderId);

            if ($order->status === 'delivered') {
                throw new RuntimeException("Cannot cancel a delivered order.");
            }

            $order->product->increment('current_stock', $order->quantity);

            $order->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Toy order cancelled, stock restored', [
                'order_id' => $order->id,
                'restored_quantity' => $order->quantity,
                'reason' => $reason,
                'correlation_id' => $correlationId,
            ]);

            return $order->refresh();
        });
    }
}
