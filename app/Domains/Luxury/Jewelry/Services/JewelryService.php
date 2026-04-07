<?php declare(strict_types=1);

namespace App\Domains\Luxury\Jewelry\Services;




use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class JewelryService
{

    public function __construct(private readonly FraudControlService $fraud,
            private readonly WalletService $wallet,
            private readonly InventoryManagementService $inventory,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}

        /**
         * Заказ ювелирного изделия с холдированием средств (Escrow).
         */
        public function purchase(int $itemId, int $userId, int $tenantId, string $correlationId = ""): JewelryOrder
        {
            $correlationId = $correlationId ?: (string) Str::uuid();

            if ($this->rateLimiter->tooManyAttempts("jewelry:purchase:".$userId, 5)) {
                throw new \RuntimeException("Jewelry purchase frequency limit exceeded.", 429);
            }
            $this->rateLimiter->hit("jewelry:purchase:".$userId, 3600);

            return $this->db->transaction(function () use ($itemId, $userId, $tenantId, $correlationId) {
                $item = JewelryItem::where("tenant_id", $tenantId)->findOrFail($itemId);

                // 1. Проверка ПОД/ФТ (ФЗ-115) для дорогих изделий (>600к)
                if ($item->price_kopecks >= 60000000) {
                    $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
                } else {
                    $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
                }

                // 2. Резерв остатка (Inventory)
                $this->inventory->reserveStock($item->id, 1, "jewelry_order", 0);

                $fee = (int) ($item->price_kopecks * 0.14);

                // 3. Создание заказа
                $order = JewelryOrder::create([
                    "uuid" => (string) Str::uuid(),
                    "tenant_id" => $tenantId,
                    "user_id" => $userId,
                    "jewelry_item_id" => $itemId,
                    "amount" => $item->price_kopecks,
                    "fee_amount" => $fee,
                    "status" => "awaiting_delivery",
                    "correlation_id" => $correlationId,
                    "tags" => ["escrow", "giis_dmdk:pending"]
                ]);

                // 4. Escrow Hold
                $this->wallet->hold(
                    $userId,
                    $item->price_kopecks,
                    \App\Domains\Wallet\Enums\BalanceTransactionType::HOLD, $correlationId, null, null, null);

                $this->logger->info("Jewelry: purchase initiated (Escrow)", [
                    "order_uuid" => $order->uuid,
                    "user_id" => $userId,
                    "item_id" => $itemId
                ]);

                return $order;
            });
        }

        /**
         * Завершение сделки (подтверждение получения/опробывания).
         */
        public function fulfill(int $orderId, string $correlationId = ""): void
        {
            $correlationId = $correlationId ?: (string) Str::uuid();
            $order = JewelryOrder::with(["jewelryItem", "user"])->findOrFail($orderId);

            $this->db->transaction(function () use ($order, $correlationId) {
                if ($order->status !== "awaiting_delivery") {
                    throw new \RuntimeException("Order cannot be fulfilled in status: {$order->status}");
                }

                $payout = $order->amount - $order->fee_amount;

                // Списание со стока окончательное
                $this->inventory->deductStock($order->jewelry_item_id, 1, "Fulfillment of order {$order->uuid}", "jewelry_order", $order->id);

                // Разморозка и перевод вендору (тенанту)
                $this->wallet->releaseHold($order->user_id, $order->amount, $correlationId);
                $this->wallet->credit($order->tenant_id, $payout, \App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT, $correlationId, null, null, [
                    "order_id" => $orderId,
                    "payout" => $payout
                ]);
            });
        }
}
