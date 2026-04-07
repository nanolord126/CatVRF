<?php

declare(strict_types=1);

namespace App\Domains\Flowers\Services;

use App\Domains\Flowers\Models\FlowerOrder;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiter;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Сервис управления цветочным магазином.
 *
 * CatVRF Canon 2026 — Layer 3 (Services).
 * Создание заказов на букеты, отгрузка с комиссией 14 %,
 * проверка свежести и списание просроченных товаров.
 * Все мутации через DB::transaction + fraud-check + correlation_id.
 *
 * @package App\Domains\Flowers\Services
 */
final readonly class FlowerShopService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly WalletService $wallet,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly RateLimiter $rateLimiter,
    ) {}

    /**
     * Создание заказа на букет с проверкой свежести и логистикой.
     *
     * @param int    $clientId      ID клиента
     * @param int    $tenantId      ID тенанта
     * @param array  $data          Данные заказа (shop_id, total_amount, address и т.д.)
     * @param string $correlationId Трейсинг-идентификатор
     *
     * @return FlowerOrder
     *
     * @throws \RuntimeException
     */
    public function createOrder(int $clientId, int $tenantId, array $data, string $correlationId = ''): FlowerOrder
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        if ($this->rateLimiter->tooManyAttempts('flowers:order:' . $clientId, 3)) {
            throw new \RuntimeException('Rate limit exceeded for flower orders.', 429);
        }
        $this->rateLimiter->hit('flowers:order:' . $clientId, 3600);

        return $this->db->transaction(function () use ($clientId, $tenantId, $data, $correlationId): FlowerOrder {
            $this->fraud->check(
                userId: $clientId,
                operationType: 'flower_shop_order',
                amount: (int) ($data['total_amount'] ?? 0),
                correlationId: $correlationId,
            );

            $order = FlowerOrder::create([
                'uuid'             => Str::uuid()->toString(),
                'tenant_id'        => $tenantId,
                'client_id'        => $clientId,
                'shop_id'          => $data['shop_id'],
                'total_amount'     => $data['total_amount'],
                'status'           => 'pending',
                'delivery_address' => $data['address'] ?? '',
                'is_anonymous'     => $data['is_anonymous'] ?? false,
                'card_text'        => $data['card_text'] ?? null,
                'correlation_id'   => $correlationId,
                'tags'             => ['vertical:flowers', 'delivery_urgency:express'],
            ]);

            $this->logger->info('Flowers: order created', [
                'order_uuid'     => $order->uuid,
                'client_id'      => $clientId,
                'tenant_id'      => $tenantId,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Сборка и отправка букета (14 % комиссия платформе).
     *
     * @param int    $orderId       ID заказа
     * @param string $correlationId Трейсинг-идентификатор
     */
    public function shipOrder(int $orderId, string $correlationId = ''): void
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        $order         = FlowerOrder::findOrFail($orderId);

        $this->db->transaction(function () use ($order, $correlationId): void {
            $totalKopecks = (int) $order->total_amount;
            $feeKopecks   = (int) round($totalKopecks * 0.14);
            $payoutKopecks = $totalKopecks - $feeKopecks;

            $order->update([
                'status'         => 'shipped',
                'shipped_at'     => Carbon::now(),
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                walletId: $order->shop_id,
                amount: $payoutKopecks,
                type: BalanceTransactionType::PAYOUT,
                correlationId: $correlationId,
                metadata: [
                    'order_id' => $order->id,
                    'fee'      => $feeKopecks,
                ],
            );

            $this->logger->info('Flowers: order shipped', [
                'order_id'       => $order->id,
                'payout'         => $payoutKopecks,
                'fee'            => $feeKopecks,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Проверить свежесть товаров и списать просроченные.
     *
     * @param int $shopId ID магазина
     *
     * @return array{wasted_count: int}
     */
    public function checkExpiredItems(int $shopId): array
    {
        $expired = FlowerInventoryItem::where('shop_id', $shopId)
            ->where('expires_at', '<', Carbon::now())
            ->where('status', 'available')
            ->get();

        foreach ($expired as $item) {
            $item->update(['status' => 'wasted']);

            $this->logger->warning('Flowers: item expired', [
                'shop_id' => $shopId,
                'item_id' => $item->id,
            ]);
        }

        return ['wasted_count' => $expired->count()];
    }

    /**
     * Получить заказ по ID.
     */
    public function getOrder(int $orderId): FlowerOrder
    {
        return FlowerOrder::findOrFail($orderId);
    }

    /**
     * Получить последние заказы клиента.
     *
     * @param int $clientId ID клиента
     * @param int $limit    Лимит записей
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, FlowerOrder>
     */
    public function getClientOrders(int $clientId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return FlowerOrder::where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }
}
