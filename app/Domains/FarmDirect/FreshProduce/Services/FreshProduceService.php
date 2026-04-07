<?php declare(strict_types=1);

namespace App\Domains\FarmDirect\FreshProduce\Services;

use App\Domains\FarmDirect\FreshProduce\Models\FreshProduceOrder;
use App\Domains\FarmDirect\FreshProduce\Models\FreshProduceItem;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\Inventory\InventoryManagementService;
use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class FreshProduceService
{
    private const COMMISSION_RATE = 0.10;
    private const RATE_LIMIT_KEY = 'fresh:order:';
    private const RATE_LIMIT_MAX = 20;
    private const RATE_LIMIT_DECAY = 3600;

    public function __construct(
        private FraudControlService $fraud,
        private WalletService $wallet,
        private AuditService $audit,
        private InventoryManagementService $inventory,
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {}

    /**
     * Создать заказ на свежие продукты.
     */
    public function createOrder(int $farmId, array $items, string $deliveryAddress, string $correlationId = ''): FreshProduceOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $userId = (int) $this->guard->id();

        return $this->db->transaction(function () use ($farmId, $items, $deliveryAddress, $correlationId, $userId): FreshProduceOrder {
            $total = 0;

            foreach ($items as $item) {
                $product = FreshProduceItem::where('id', $item['product_id'])
                    ->where('farm_id', $farmId)
                    ->firstOrFail();

                if ($product->stock_kg < $item['weight_kg']) {
                    throw new \RuntimeException("Not enough stock for item: {$product->id}", 400);
                }

                $total += (int) ($product->price_per_kg_kopecks * $item['weight_kg']);
            }

            $this->fraud->check(
                userId: $userId,
                operationType: 'fresh_produce_order',
                amount: $total,
                correlationId: $correlationId,
            );

            $payoutKopecks = $total - (int) ($total * self::COMMISSION_RATE);

            $order = FreshProduceOrder::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'farm_id' => $farmId,
                'client_id' => $userId,
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'total_kopecks' => $total,
                'payout_kopecks' => $payoutKopecks,
                'payment_status' => 'pending',
                'items_json' => $items,
                'delivery_address' => $deliveryAddress,
                'tags' => ['fresh_produce' => true],
            ]);

            $this->audit->log(
                action: 'fresh_produce_order_created',
                subjectType: FreshProduceOrder::class,
                subjectId: $order->id,
                old: [],
                new: $order->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Fresh produce order created', [
                'order_id' => $order->id,
                'farm_id' => $farmId,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Завершить заказ и выплатить фермеру.
     */
    public function completeOrder(int $orderId, string $correlationId = ''): FreshProduceOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($orderId, $correlationId): FreshProduceOrder {
            $order = FreshProduceOrder::findOrFail($orderId);

            if ($order->payment_status !== 'completed') {
                throw new \RuntimeException('Order payment not completed', 400);
            }

            $order->update([
                'status' => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                walletId: $order->tenant_id,
                amount: $order->payout_kopecks,
                type: BalanceTransactionType::PAYOUT,
                correlationId: $correlationId,
                metadata: ['order_id' => $order->id, 'farm_id' => $order->farm_id],
            );

            $this->audit->log(
                action: 'fresh_produce_order_completed',
                subjectType: FreshProduceOrder::class,
                subjectId: $order->id,
                old: ['status' => 'pending_payment'],
                new: ['status' => 'completed'],
                correlationId: $correlationId,
            );

            return $order;
        });
    }

    /**
     * Отменить заказ и вернуть оплату.
     */
    public function cancelOrder(int $orderId, string $correlationId = ''): FreshProduceOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($orderId, $correlationId): FreshProduceOrder {
            $order = FreshProduceOrder::findOrFail($orderId);

            if ($order->status === 'completed') {
                throw new \RuntimeException('Cannot cancel completed order', 400);
            }

            $previousStatus = $order->payment_status;

            $order->update([
                'status' => 'cancelled',
                'payment_status' => 'refunded',
                'correlation_id' => $correlationId,
            ]);

            if ($previousStatus === 'completed') {
                $this->wallet->credit(
                    walletId: $order->tenant_id,
                    amount: $order->total_kopecks,
                    type: BalanceTransactionType::REFUND,
                    correlationId: $correlationId,
                    metadata: ['order_id' => $order->id],
                );
            }

            $this->audit->log(
                action: 'fresh_produce_order_cancelled',
                subjectType: FreshProduceOrder::class,
                subjectId: $order->id,
                old: ['status' => $previousStatus],
                new: ['status' => 'cancelled'],
                correlationId: $correlationId,
            );

            return $order;
        });
    }

    /**
     * Получить заказ по идентификатору.
     */
    public function getOrder(int $orderId): FreshProduceOrder
    {
        return FreshProduceOrder::findOrFail($orderId);
    }

    /**
     * Получить список заказов клиента.
     */
    public function getUserOrders(int $clientId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return FreshProduceOrder::where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }
}
