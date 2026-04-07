<?php declare(strict_types=1);

namespace App\Domains\MeatShops\Services;




use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class MeatService
{

    public function __construct(private readonly FraudControlService $fraud,
            private readonly InventoryManagementService $inventory,
            private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}

        /**
         * Создание заказа на мясо/мясные наборы.
         */
        public function createOrder(int $supplierId, array $items, string $correlationId = ""): MeatOrder
        {
            $correlationId = $correlationId ?: (string) Str::uuid();

            // 1. Rate Limiting - защита от спама заказами
            if ($this->rateLimiter->tooManyAttempts("meat:order:".$this->guard->id(), 3)) {
                throw new \RuntimeException("Too many orders. Wait for cooldown.", 429);
            }
            $this->rateLimiter->hit("meat:order:".$this->guard->id(), 3600);

            return $this->db->transaction(function () use ($supplierId, $items, $correlationId) {
                $supplier = MeatSupplier::findOrFail($supplierId);

                // 2. Fraud Check - проверка поставщика и клиента
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

                $totalPrice = 0;
                foreach ($items as $item) {
                    $product = MeatProduct::findOrFail($item["id"]);
                    $totalPrice += ($product->price_per_gram * $item["grams"]);

                    // 3. Резервация веса в Inventory
                    $this->inventory->reserveStock(
                        itemId: $product->id,
                        quantity: $item["grams"],
                        sourceType: "meat_order",
                        sourceId: 0
                    );
                }

                $order = MeatOrder::create([
                    "uuid" => (string) Str::uuid(),
                    "tenant_id" => $supplier->tenant_id,
                    "supplier_id" => $supplierId,
                    "client_id" => $this->guard->id(),
                    "status" => "pending_butcher", // Передано мяснику на разделку
                    "total_price_kopecks" => $totalPrice,
                    "correlation_id" => $correlationId,
                    "tags" => ["halal:yes", "fresh:premium", "vacuum_packed:yes"]
                ]);

                $this->logger->info("Meat: order sent to butcher", ["order_id" => $order->id, "total" => $totalPrice]);

                return $order;
            });
        }

        /**
         * Подтверждение готовности заказа (после разделки и взвешивания).
         */
        public function readyForDelivery(int $orderId, int $finalWeightGrams, string $correlationId = ""): void
        {
            $correlationId = $correlationId ?: (string) Str::uuid();
            $order = MeatOrder::findOrFail($orderId);

            $this->db->transaction(function () use ($order, $finalWeightGrams, $correlationId) {
                // Корректировка цены по реальному весу (если отличается от планового)
                $product = MeatProduct::find($order->product_id);
                $finalPrice = $finalWeightGrams * ($product->price_per_gram ?? 0);

                $order->update([
                    "total_price_kopecks" => $finalPrice,
                    "status" => "ready_for_delivery",
                    "ready_at" => now()
                ]);

                // 4. Окончательное списание веса
                $this->inventory->deductStock(
                    itemId: $order->product_id,
                    quantity: $finalWeightGrams,
                    reason: "Butchery finished for Order #{$order->id}",
                    sourceType: "meat_order",
                    sourceId: $order->id
                );

                $this->logger->info("Meat: order weighed and ready", ["order_id" => $order->id, "weight" => $finalWeightGrams]);
            });
        }

        /**
         * Выплата магазину/фермеру после подтверждения доставки (14% комиссия).
         */
        public function finalizePayout(int $orderId, string $correlationId = ""): void
        {
            $correlationId = $correlationId ?: (string) Str::uuid();
            $order = MeatOrder::with("supplier")->findOrFail($orderId);

            $this->db->transaction(function () use ($order, $correlationId) {
                $total = $order->total_price_kopecks;
                $fee = (int) ($total * 0.14);
                $payout = $total - $fee;

                $this->wallet->credit(
                    userId: $order->supplier->owner_id,
                    amount: $payout,
                    type: "meat_payout",
                    reason: "Order delivered: {$order->id}",
                    correlationId: $correlationId
                );

                $order->update(["status" => "completed", \App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT, $correlationId, null, null, ["order_id" => $order->id, "payout" => $payout]);
            });
        }
}
