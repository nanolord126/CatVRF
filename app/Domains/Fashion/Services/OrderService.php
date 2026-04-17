<?php declare(strict_types=1);

namespace App\Domains\Fashion\Services;


use App\Domains\Payment\Services\PaymentServiceAdapter;
use App\Domains\Payment\Services\PaymentService;
use Illuminate\Cache\RateLimiter;
use Carbon\Carbon;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class OrderService
{

    public function __construct(private readonly FraudControlService $fraud,
            private readonly InventoryManagementService $inventory,
            private readonly PaymentServiceAdapter $payment,
            private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}

        /**
         * Создание заказа на одежду/обувь.
         * Реализована поддержка "Try before you buy" (Примерка).
         */
        public function createOrder(int $brandId, array $items, bool $requiresFitting = false, string $correlationId = ""): FashionOrder
        {
            $correlationId = $correlationId ?: (string) Str::uuid();

            // 1. Rate Limiting - защита от выкупа всего стока ботами
            if ($this->rateLimiter->tooManyAttempts("fashion:order:".$this->guard->id(), 5)) {
                throw new \RuntimeException("Too many orders. Wait.", 429);
            }
            $this->rateLimiter->hit("fashion:order:".$this->guard->id(), 3600);

            return $this->db->transaction(function () use ($brandId, $items, $requiresFitting, $correlationId) {
                $brand = FashionBrand::findOrFail($brandId);

                // 2. Fraud Check - проверка на массовые возвраты и поддельные аккаунты
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

                if ($fraud["decision"] === "block") {
                    $this->logger->warning("Fashion Block", ["user" => $this->guard->id(), "score" => $fraud["score"]]);
                    throw new \RuntimeException("Blocked by security. High return risk detected.", 403);
                }

                $totalPrice = 0;
                foreach ($items as $item) {
                    $product = FashionProduct::findOrFail($item["id"]);
                    $totalPrice += ($product->price_kopecks * $item["qty"]);

                    // 3. Резервация стока (InventoryManagementService)
                    // Обязательно учитываем размер (size_id) в мета-данных
                    $this->inventory->reserveStock(
                        itemId: $product->id,
                        quantity: $item["qty"],
                        sourceType: "fashion_order",
                        sourceId: 0
                    );
                }

                // 4. Создание заказа
                $order = FashionOrder::create([
                    "uuid" => (string) Str::uuid(),
                    "tenant_id" => $brand->tenant_id,
                    "brand_id" => $brandId,
                    "client_id" => $this->guard->id(),
                    "status" => "pending_payment",
                    "total_price_kopecks" => $totalPrice,
                    "requires_fitting" => $requiresFitting,
                    "correlation_id" => $correlationId,
                    "tags" => ["collection:spring_2026", "fitting:".($requiresFitting ? "yes" : "no")]
                ]);

                $this->logger->info("Fashion: order created", ["order_id" => $order->id, "fitting" => $requiresFitting]);

                return $order;
            });
        }

        /**
         * Обработка возврата после примерки.
         */
        public function processFittingResult(int $orderId, array $keptItemIds, string $correlationId = ""): void
        {
            $correlationId = $correlationId ?: (string) Str::uuid();
            $order = FashionOrder::with("items")->findOrFail($orderId);

            $this->db->transaction(function () use ($order, $keptItemIds, $correlationId) {
                foreach ($order->items as $item) {
                    if (!in_array($item->id, $keptItemIds)) {
                        // Возвращаем невыкупленный товар на склад
                        $this->inventory->releaseStock(
                            itemId: $item->id,
                            quantity: $item->pivot->quantity,
                            sourceType: "fashion_order",
                            sourceId: $order->id
                        );

                        FashionReturn::create([
                            "order_id" => $order->id,
                            "product_id" => $item->id,
                            "reason" => "Fitting: did not fit",
                            "correlation_id" => $correlationId
                        ]);
                    }
                }

                // 5. Пересчет финальной стоимости (только за выкупленное)
                $newTotal = $order->items->whereIn("id", $keptItemIds)->sum(fn($i) => $i->price_kopecks * $i->pivot->quantity);
                $order->update(["total_price_kopecks" => $newTotal, "status" => "partially_returned"]);

                $this->logger->info("Fashion: fitting processed", ["order_id" => $order->id, "kept" => count($keptItemIds)]);
            });
        }

        /**
         * Завершение заказа и выплата бренду (14% комиссия).
         */
        public function completeOrder(int $orderId, string $correlationId = ""): void
        {
            $correlationId = $correlationId ?: (string) Str::uuid();
            $order = FashionOrder::with("brand")->findOrFail($orderId);

            $this->db->transaction(function () use ($order, $correlationId) {
                $order->update(["status" => "completed", "finalized_at" => Carbon::now()]);

                // 6. Окончательное списание из Inventory
                $this->inventory->deductStock(
                    itemId: 0,
                    quantity: 1,
                    reason: "Fashion delivery confirmed: {$order->id}",
                    sourceType: "fashion_order",
                    sourceId: $order->id
                );

                // 7. Расчет комиссии платформы (Канон 2026: 14%)
                $multiplier = 0.14;
                $total = $order->total_price_kopecks;
                $platformFee = (int) ($total * $multiplier);
                $payout = $total - $platformFee;

                // Выплата бренду
                $this->wallet->credit(
                    userId: $order->brand->owner_id,
                    amount: $payout,
                    type: "fashion_payout",
                    reason: "Order finalized: {$order->id}",
                    correlationId: $correlationId
                );

                $this->logger->info("Fashion: payout done", ["order_id" => $order->id, "payout" => $payout]);
            });
        }
}
