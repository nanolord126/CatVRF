<?php declare(strict_types=1);

namespace App\Domains\Electronics\Services;

use App\Domains\Electronics\Models\ElectronicOrder;
use App\Domains\Electronics\Models\ElectronicProduct;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class ElectronicsService
{
    private const COMMISSION_RATE = 0.14;
    private const RATE_LIMIT_KEY = 'electronics:order:';
    private const RATE_LIMIT_MAX = 15;
    private const RATE_LIMIT_DECAY = 3600;

    public function __construct(
        private FraudControlService $fraud,
        private WalletService $wallet,
        private AuditService $audit,
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {}

    /**
     * Создать заказ электроники.
     */
    public function createOrder(int $sellerId, array $items, string $correlationId = ''): ElectronicOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $userId = (int) $this->guard->id();

        return $this->db->transaction(function () use ($sellerId, $items, $correlationId, $userId): ElectronicOrder {
            $total = 0;

            foreach ($items as $item) {
                $product = ElectronicProduct::where('id', $item['product_id'])->firstOrFail();
                $total += $product->price_kopecks * $item['quantity'];

                if ($product->stock < $item['quantity']) {
                    throw new \RuntimeException('Out of stock for product: ' . $product->id, 400);
                }
            }

            $this->fraud->check(
                userId: $userId,
                operationType: 'electronic_order',
                amount: $total,
                correlationId: $correlationId,
            );

            $payoutKopecks = $total - (int) ($total * self::COMMISSION_RATE);

            $order = ElectronicOrder::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'seller_id' => $sellerId,
                'client_id' => $userId,
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'total_kopecks' => $total,
                'payout_kopecks' => $payoutKopecks,
                'payment_status' => 'pending',
                'items_json' => $items,
                'tags' => ['electronics' => true],
            ]);

            $this->audit->record(
                action: 'electronic_order_created',
                subjectType: ElectronicOrder::class,
                subjectId: $order->id,
                oldValues: [],
                newValues: $order->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Electronic order created', [
                'order_id' => $order->id,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Завершить заказ и выплатить продавцу.
     */
    public function completeOrder(int $orderId, string $correlationId = ''): ElectronicOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($orderId, $correlationId): ElectronicOrder {
            $order = ElectronicOrder::findOrFail($orderId);

            if ($order->payment_status !== 'completed') {
                throw new \RuntimeException('Order not paid', 400);
            }

            foreach ($order->items_json as $item) {
                ElectronicProduct::findOrFail($item['product_id'])->decrement('stock', $item['quantity']);
            }

            $order->update([
                'status' => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                walletId: $order->tenant_id,
                amount: $order->payout_kopecks,
                reason: 'electronics_payout',
                correlationId: $correlationId,
            );

            $this->audit->record(
                action: 'electronic_order_completed',
                subjectType: ElectronicOrder::class,
                subjectId: $order->id,
                oldValues: ['status' => 'pending_payment'],
                newValues: ['status' => 'completed'],
                correlationId: $correlationId,
            );

            return $order;
        });
    }

    /**
     * Отменить заказ и вернуть оплату.
     */
    public function cancelOrder(int $orderId, string $correlationId = ''): ElectronicOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($orderId, $correlationId): ElectronicOrder {
            $order = ElectronicOrder::findOrFail($orderId);

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
                    reason: 'electronics_refund',
                    correlationId: $correlationId,
                );
            }

            $this->audit->record(
                action: 'electronic_order_cancelled',
                subjectType: ElectronicOrder::class,
                subjectId: $order->id,
                oldValues: ['status' => $previousStatus],
                newValues: ['status' => 'cancelled'],
                correlationId: $correlationId,
            );

            return $order;
        });
    }

    /**
     * Получить заказ по идентификатору.
     */
    public function getOrder(int $orderId): ElectronicOrder
    {
        return ElectronicOrder::findOrFail($orderId);
    }

    /**
     * Получить список заказов клиента.
     */
    public function getUserOrders(int $clientId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return ElectronicOrder::where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }
}
