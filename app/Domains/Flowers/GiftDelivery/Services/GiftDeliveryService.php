<?php

declare(strict_types=1);

namespace App\Domains\Flowers\GiftDelivery\Services;

use App\Services\FraudControlService;
use App\Services\WalletService;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Сервис доставки подарков.
 *
 * CatVRF Canon 2026 — Layer 3 (Services).
 * Управляет заказами на подарочную доставку: создание, завершение, отмена.
 * Все мутации через DB::transaction + fraud-check + correlation_id.
 *
 * @package App\Domains\Flowers\GiftDelivery\Services
 */
final readonly class GiftDeliveryService
{
    public function __construct(
        private FraudControlService $fraud,
        private WalletService $wallet,
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
        private RateLimiter $rateLimiter,
    ) {}

    /**
     * Создать заказ на доставку подарка.
     *
     * @param int    $vendorId         ID поставщика
     * @param string $giftType         Тип подарка
     * @param string $recipientAddress Адрес получателя
     * @param string $deliveryDate     Дата доставки
     * @param string $correlationId    Трейсинг-идентификатор
     *
     * @return GiftOrder
     *
     * @throws \RuntimeException
     */
    public function createOrder(
        int $vendorId,
        string $giftType,
        string $recipientAddress,
        string $deliveryDate,
        string $correlationId = '',
    ): GiftOrder {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $userId        = $this->guard->id() ?? 0;

        if ($this->rateLimiter->tooManyAttempts('gift:order:' . $userId, 15)) {
            throw new \RuntimeException('Too many requests', 429);
        }
        $this->rateLimiter->hit('gift:order:' . $userId, 3600);

        return $this->db->transaction(function () use (
            $vendorId,
            $giftType,
            $recipientAddress,
            $deliveryDate,
            $correlationId,
            $userId,
        ): GiftOrder {
            $this->fraud->check(
                userId: $userId,
                operationType: 'gift_order',
                amount: 0,
                correlationId: $correlationId,
            );

            $order = GiftOrder::create([
                'uuid'              => Str::uuid()->toString(),
                'tenant_id'         => tenant()->id,
                'vendor_id'         => $vendorId,
                'sender_id'         => $userId,
                'correlation_id'    => $correlationId,
                'status'            => 'pending_payment',
                'payment_status'    => 'pending',
                'gift_type'         => $giftType,
                'recipient_address' => $recipientAddress,
                'delivery_date'     => $deliveryDate,
                'tags'              => ['gift' => true],
            ]);

            $this->logger->info('Gift order created', [
                'order_id'       => $order->id,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Завершить заказ и выплатить вендору (минус комиссия 14 %).
     *
     * @param int    $orderId       ID заказа
     * @param string $correlationId Трейсинг-идентификатор
     *
     * @return GiftOrder
     */
    public function completeOrder(int $orderId, string $correlationId = ''): GiftOrder
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        return $this->db->transaction(function () use ($orderId, $correlationId): GiftOrder {
            $order = GiftOrder::findOrFail($orderId);

            if ($order->payment_status !== 'completed') {
                throw new \RuntimeException('Order not paid', 400);
            }

            $order->update([
                'status'         => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $payoutKopecks = $order->payout_kopecks ?? (int) ($order->total_kopecks * 0.86);

            $this->wallet->credit(
                walletId: $order->vendor_id,
                amount: $payoutKopecks,
                type: BalanceTransactionType::PAYOUT,
                correlationId: $correlationId,
                metadata: ['order_id' => $order->id],
            );

            $this->logger->info('Gift order completed', [
                'order_id'       => $order->id,
                'payout'         => $payoutKopecks,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Отменить заказ. Если оплачен — рефанд отправителю.
     *
     * @param int    $orderId       ID заказа
     * @param string $correlationId Трейсинг-идентификатор
     *
     * @return GiftOrder
     */
    public function cancelOrder(int $orderId, string $correlationId = ''): GiftOrder
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        return $this->db->transaction(function () use ($orderId, $correlationId): GiftOrder {
            $order = GiftOrder::findOrFail($orderId);

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
                    walletId: $order->sender_id,
                    amount: $order->total_kopecks,
                    type: BalanceTransactionType::REFUND,
                    correlationId: $correlationId,
                    metadata: ['order_id' => $order->id],
                );
            }

            $this->logger->info('Gift order cancelled', [
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
    public function getOrder(int $orderId): GiftOrder
    {
        return GiftOrder::findOrFail($orderId);
    }

    /**
     * Получить последние заказы отправителя.
     *
     * @param int $senderId ID отправителя
     * @param int $limit    Лимит записей
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, GiftOrder>
     */
    public function getUserOrders(int $senderId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return GiftOrder::where('sender_id', $senderId)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }
}
