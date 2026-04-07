<?php

declare(strict_types=1);

namespace App\Domains\PartySupplies\Gifts\Services;

use App\Domains\PartySupplies\Gifts\Models\Gift;
use App\Domains\PartySupplies\Gifts\Models\GiftOrder;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Сервис управления подарками и подарочными заказами.
 *
 * Обрабатывает: создание заказа на подарок, управление упаковкой,
 * списание средств, начисление поставщику, аудит всех операций.
 *
 * @throws \RuntimeException
 * @throws \DomainException
 */
final readonly class GiftService
{
    public function __construct(
        private FraudControlService $fraud,
        private WalletService $wallet,
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
        private RateLimiter $rateLimiter,
        private AuditService $audit,
    ) {}

    /**
     * Создание заказа на подарок с индивидуальной упаковкой.
     *
     * @param int    $clientId      Идентификатор клиента
     * @param array  $data          Данные заказа (gift_id, total_amount, wrapping_id и т.д.)
     * @param string $correlationId Идентификатор корреляции
     *
     * @throws \RuntimeException  При превышении лимита запросов
     * @throws \DomainException   При нехватке средств или отсутствии товара
     */
    public function orderGift(int $clientId, array $data, string $correlationId = ''): GiftOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $gift = Gift::findOrFail($data['gift_id']);

        if ($this->rateLimiter->tooManyAttempts('gifts:order:' . $clientId, 3)) {
            throw new \RuntimeException('Rate limit exceeded for gift orders.', 429);
        }
        $this->rateLimiter->hit('gifts:order:' . $clientId, 3600);

        return $this->db->transaction(function () use ($clientId, $gift, $data, $correlationId): GiftOrder {
            $this->fraud->check(
                userId: $this->guard->id() ?? 0,
                operationType: 'gift_order',
                amount: $data['total_amount'],
                correlationId: $correlationId,
            );

            $totalAmount = (int) $data['total_amount'];
            $fee = (int) ($totalAmount * 0.14);
            $payout = $totalAmount - $fee;

            $order = GiftOrder::create([
                'uuid' => Str::uuid()->toString(),
                'tenant_id' => tenant()->id,
                'client_id' => $clientId,
                'gift_id' => $gift->id,
                'correlation_id' => $correlationId,
                'status' => 'pending',
                'total_kopecks' => $totalAmount,
                'payout_kopecks' => $payout,
                'fee_kopecks' => $fee,
                'wrapping_id' => $data['wrapping_id'] ?? null,
                'message' => $data['message'] ?? null,
                'tags' => ['gift' => true],
            ]);

            $this->wallet->debit(
                $clientId,
                $totalAmount,
                BalanceTransactionType::WITHDRAWAL,
                $correlationId,
            );

            $this->wallet->credit(
                $gift->tenant_id,
                $payout,
                BalanceTransactionType::PAYOUT,
                $correlationId,
            );

            $this->audit->record(
                action: 'gift_order_created',
                subjectType: GiftOrder::class,
                subjectId: $order->id,
                newValues: ['total' => $totalAmount, 'payout' => $payout, 'fee' => $fee],
                correlationId: $correlationId,
            );

            $this->logger->info('Gift order created', [
                'order_id' => $order->id,
                'gift_id' => $gift->id,
                'client_id' => $clientId,
                'total' => $totalAmount,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }
}
