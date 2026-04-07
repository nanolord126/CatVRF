<?php declare(strict_types=1);

namespace App\Domains\Furniture\Services;

use App\Domains\Furniture\Models\FurnitureOrder;
use App\Domains\Furniture\Models\FurnitureItem;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * FurnitureService — управление заказами мебели.
 *
 * Полный цикл: создание, завершение, отмена заказов
 * с fraud-check, wallet-интеграцией и audit-логированием.
 *
 * @package App\Domains\Furniture\Services
 */
final readonly class FurnitureService
{
    public function __construct(
        private FraudControlService $fraud,
        private WalletService $wallet,
        private AuditService $audit,
        private \Illuminate\Database\DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {}

    /**
     * Создать заказ мебели.
     */
    public function createOrder(
        int $sellerId,
        array $items,
        string $correlationId = '',
    ): FurnitureOrder {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $userId = (int) ($this->guard->id() ?? 0);

        $this->fraud->check(
            userId: $userId,
            operationType: 'furniture_order_create',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($sellerId, $items, $correlationId, $userId) {
            $total = 0;
            foreach ($items as $item) {
                $product = FurnitureItem::findOrFail($item['item_id']);
                $total += (int) ($product->price * ($item['quantity'] ?? 1));
            }

            $order = FurnitureOrder::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'seller_id' => $sellerId,
                'client_id' => $userId,
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'total_kopecks' => $total,
                'payout_kopecks' => $total - (int) ($total * 0.14),
                'payment_status' => 'pending',
                'items_json' => $items,
                'tags' => ['furniture' => true],
            ]);

            $this->audit->log(
                action: 'furniture_order_created',
                subjectType: FurnitureOrder::class,
                subjectId: $order->id,
                old: [],
                new: $order->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Furniture order created', [
                'order_id' => $order->id,
                'seller_id' => $sellerId,
                'total' => $total,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Завершить заказ, списать товары и выплатить продавцу.
     */
    public function completeOrder(int $orderId, string $correlationId = ''): FurnitureOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($orderId, $correlationId) {
            $order = FurnitureOrder::findOrFail($orderId);

            if ($order->payment_status !== 'completed') {
                throw new \RuntimeException('Payment not completed', 400);
            }

            foreach ($order->items_json as $item) {
                FurnitureItem::findOrFail($item['item_id'])
                    ->decrement('stock', $item['quantity'] ?? 1);
            }

            $order->update([
                'status' => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                walletId: $order->seller_id,
                amount: $order->payout_kopecks,
                type: 'furniture_payout',
                correlationId: $correlationId,
                metadata: ['order_id' => $order->id, 'vertical' => 'furniture'],
            );

            $this->audit->log(
                action: 'furniture_order_completed',
                subjectType: FurnitureOrder::class,
                subjectId: $order->id,
                old: ['status' => 'pending_payment'],
                new: ['status' => 'completed'],
                correlationId: $correlationId,
            );

            $this->logger->info('Furniture order completed', [
                'order_id' => $order->id,
                'payout' => $order->payout_kopecks,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Отменить заказ и вернуть средства.
     */
    public function cancelOrder(int $orderId, string $correlationId = ''): FurnitureOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($orderId, $correlationId) {
            $order = FurnitureOrder::findOrFail($orderId);

            if ($order->status === 'completed') {
                throw new \RuntimeException('Cannot cancel a completed order', 400);
            }

            $oldStatus = $order->status;
            $order->update([
                'status' => 'cancelled',
                'payment_status' => 'refunded',
                'correlation_id' => $correlationId,
            ]);

            if ($order->payment_status === 'completed') {
                $this->wallet->credit(
                    walletId: $order->client_id,
                    amount: $order->total_kopecks,
                    type: 'furniture_refund',
                    correlationId: $correlationId,
                    metadata: ['order_id' => $order->id, 'reason' => 'order_cancelled'],
                );
            }

            $this->audit->log(
                action: 'furniture_order_cancelled',
                subjectType: FurnitureOrder::class,
                subjectId: $order->id,
                old: ['status' => $oldStatus],
                new: ['status' => 'cancelled'],
                correlationId: $correlationId,
            );

            $this->logger->info('Furniture order cancelled', [
                'order_id' => $order->id,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Получить заказ по ID.
     */
    public function getOrder(int $orderId): FurnitureOrder
    {
        return FurnitureOrder::findOrFail($orderId);
    }

    /**
     * Получить список заказов клиента.
     */
    public function getUserOrders(int $clientId): \Illuminate\Database\Eloquent\Collection
    {
        return FurnitureOrder::where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
    }
}
