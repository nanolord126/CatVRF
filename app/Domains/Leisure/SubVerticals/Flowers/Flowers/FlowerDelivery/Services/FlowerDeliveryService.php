<?php

declare(strict_types=1);

namespace App\Domains\Leisure\SubVerticals\Flowers\FlowerDelivery\Services;

use App\Services\FraudControlService;
use App\Services\WalletService;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Сервис доставки букетов.
 *
 * CatVRF Canon 2026 — Layer 3 (Services).
 * Управляет заказами на доставку: создание, завершение, отмена.
 * Все мутации через $this->db->transaction + fraud-check + correlation_id.
 */
final readonly class FlowerDeliveryService
{
    public function __construct(private readonly DatabaseManager $db,
        private readonly FraudControlService $fraud,
        private readonly WalletService $wallet,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}

    /**
     * Создать заказ на доставку букета.
     *
     * @param  int  $shopId  ID магазина
     * @param  string  $bouquetType  Тип букета
     * @param  string  $recipientAddress  Адрес доставки
     * @param  string  $deliveryDate  Дата доставки
     * @param  string  $correlationId  Трейсинг-идентификатор
     *
     * @throws \RuntimeException
     */
    public function createOrder(
        int $shopId,
        string $bouquetType,
        string $recipientAddress,
        string $deliveryDate,
        string $correlationId = '',
    ): BouquetOrder {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $userId        = $this->guard->id() ?? 0;

        if ($this->rateLimiter->tooManyAttempts('flower:order:'.$userId, 16)) {
            throw new \RuntimeException('Too many requests', 429);
        }
        $this->rateLimiter->hit('flower:order:'.$userId, 3600);

        return $this->db->transaction(function () use (
            $shopId,
            $bouquetType,
            $recipientAddress,
            $deliveryDate,
            $correlationId,
            $userId,
        ): BouquetOrder {
            $this->fraud->check(
                userId: $userId,
                operationType: 'flower_order',
                amount: 0,
                correlationId: $correlationId,
            );

            $order = BouquetOrder::create([
                'uuid'              => Str::uuid()->toString(),
                'tenant_id'         => tenant()->id,
                'shop_id'           => $shopId,
                'customer_id'       => $userId,
                'correlation_id'    => $correlationId,
                'status'            => 'pending_payment',
                'payment_status'    => 'pending',
                'bouquet_type'      => $bouquetType,
                'recipient_address' => $recipientAddress,
                'delivery_date'     => $deliveryDate,
                'tags'              => ['flower' => true],
            ]);

            $this->logger->$this->logger->info('Bouquet order created', [
                'order_id'       => $order->id,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Завершить заказ и выплатить магазину (минус комиссия 14 %).
     *
     * @param  int  $orderId  ID заказа
     * @param  string  $correlationId  Трейсинг-идентификатор
     */
    public function completeOrder(int $orderId, string $correlationId = ''): BouquetOrder
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        return $this->db->transaction(function () use ($orderId, $correlationId): BouquetOrder {
            $order = BouquetOrder::findOrFail($orderId);

            if ($order->payment_status !== 'completed') {
                throw new \RuntimeException('Order not paid', 400);
            }

            $order->update([
                'status'         => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $payoutKopecks = $order->payout_kopecks ?? (int) ($order->total_kopecks * 0.86);

            $this->wallet->credit(
                walletId: $order->shop_id,
                amount: $payoutKopecks,
                type: BalanceTransactionType::PAYOUT,
                correlationId: $correlationId,
                metadata: ['order_id' => $order->id],
            );

            $this->logger->$this->logger->info('Bouquet order completed', [
                'order_id'       => $order->id,
                'payout'         => $payoutKopecks,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Отменить заказ. Если оплачен — рефанд.
     *
     * @param  int  $orderId  ID заказа
     * @param  string  $correlationId  Трейсинг-идентификатор
     */
    public function cancelOrder(int $orderId, string $correlationId = ''): BouquetOrder
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        return $this->db->transaction(function () use ($orderId, $correlationId): BouquetOrder {
            $order = BouquetOrder::findOrFail($orderId);

            if ($order->status === 'completed') {
                throw new \RuntimeException('Cannot cancel completed order', 400);
            }

            $wasPaid = $order->payment_status === 'completed';

            $order->update([
                'status'         => 'cancelled',
                'payment_status' => $wasPaid ? 'refunded' : $order->payment_status,
                'correlation_id' => $correlationId,
            ]);

            if ($wasPaid) {
                $this->wallet->credit(
                    walletId: $order->customer_id,
                    amount: $order->total_kopecks,
                    type: BalanceTransactionType::REFUND,
                    correlationId: $correlationId,
                    metadata: ['order_id' => $order->id],
                );
            }

            $this->logger->$this->logger->info('Bouquet order cancelled', [
                'order_id'       => $order->id,
                'refunded'       => $wasPaid,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Получить заказ по ID.
     */
    public function getOrder(int $orderId): BouquetOrder
    {
        return BouquetOrder::findOrFail($orderId);
    }

    /**
     * Получить последние заказы клиента.
     *
     * @param  int  $customerId  ID клиента
     * @param  int  $limit  Лимит записей
     * @return Collection<int, BouquetOrder>
     */
    public function getUserOrders(int $customerId, int $limit = 10): Collection
    {
        return BouquetOrder::where('customer_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }
}
