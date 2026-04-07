<?php declare(strict_types=1);

namespace App\Domains\FarmDirect\Services;

use App\Domains\FarmDirect\Models\Farm;
use App\Domains\FarmDirect\Models\FarmOrder;
use App\Domains\FarmDirect\Models\FarmProduct;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\Inventory\InventoryManagementService;
use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class FarmService
{
    private const COMMISSION_RATE = 0.12;
    private const RATE_LIMIT_KEY = 'farm:order:';
    private const RATE_LIMIT_MAX = 15;
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
     * Создать заказ у фермера.
     */
    public function createOrder(int $farmId, array $items, string $deliveryAddress, string $correlationId = ''): FarmOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $userId = (int) $this->guard->id();

        return $this->db->transaction(function () use ($farmId, $items, $deliveryAddress, $correlationId, $userId): FarmOrder {
            $farm = Farm::findOrFail($farmId);
            $total = 0;

            foreach ($items as $item) {
                $product = FarmProduct::where('id', $item['product_id'])
                    ->where('farm_id', $farmId)
                    ->firstOrFail();

                if ($product->stock < $item['quantity']) {
                    throw new \RuntimeException("Not enough stock for product: {$product->id}", 400);
                }

                $total += $product->price_kopecks * $item['quantity'];
            }

            $this->fraud->check(
                userId: $userId,
                operationType: 'farm_order',
                amount: $total,
                correlationId: $correlationId,
            );

            $payoutKopecks = $total - (int) ($total * self::COMMISSION_RATE);

            $order = FarmOrder::create([
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
                'tags' => ['farm_direct' => true],
            ]);

            foreach ($items as $item) {
                $this->inventory->reserve(
                    productId: $item['product_id'],
                    quantity: $item['quantity'],
                    orderId: $order->id,
                    correlationId: $correlationId,
                );
            }

            $this->audit->log(
                action: 'farm_order_created',
                subjectType: FarmOrder::class,
                subjectId: $order->id,
                old: [],
                new: $order->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Farm order created', [
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
    public function completeOrder(int $orderId, string $correlationId = ''): FarmOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($orderId, $correlationId): FarmOrder {
            $order = FarmOrder::findOrFail($orderId);

            if ($order->payment_status !== 'completed') {
                throw new \RuntimeException('Order payment not completed', 400);
            }

            foreach ($order->items_json as $item) {
                FarmProduct::findOrFail($item['product_id'])->decrement('stock', $item['quantity']);
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
                action: 'farm_order_completed',
                subjectType: FarmOrder::class,
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
    public function cancelOrder(int $orderId, string $correlationId = ''): FarmOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($orderId, $correlationId): FarmOrder {
            $order = FarmOrder::findOrFail($orderId);

            if ($order->status === 'completed') {
                throw new \RuntimeException('Cannot cancel completed order', 400);
            }

            $previousStatus = $order->payment_status;

            foreach ($order->items_json as $item) {
                $this->inventory->release(
                    productId: $item['product_id'],
                    quantity: $item['quantity'],
                    orderId: $order->id,
                    correlationId: $correlationId,
                );
            }

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
                action: 'farm_order_cancelled',
                subjectType: FarmOrder::class,
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
    public function getOrder(int $orderId): FarmOrder
    {
        return FarmOrder::findOrFail($orderId);
    }

    /**
     * Получить список заказов клиента.
     */
    public function getUserOrders(int $clientId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return FarmOrder::where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }

    /**
     * Получить заказы конкретного фермера.
     */
    public function getFarmOrders(int $farmId, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return FarmOrder::where('farm_id', $farmId)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }

    /**
     * Получить каталог товаров фермера.
     */
    public function getFarmProducts(int $farmId): \Illuminate\Database\Eloquent\Collection
    {
        return FarmProduct::where('farm_id', $farmId)
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->get();
    }
}
