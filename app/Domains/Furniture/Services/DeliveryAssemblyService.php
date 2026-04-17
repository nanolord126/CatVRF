<?php declare(strict_types=1);

namespace App\Domains\Furniture\Services;


use App\Domains\Payment\Services\PaymentServiceAdapter;
use App\Services\Payment\PaymentService;
use Illuminate\Cache\RateLimiter;
use Carbon\Carbon;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class DeliveryAssemblyService
{


    public function __construct(private readonly FraudControlService $fraud,
            private readonly InventoryManagementService $inventory,
            private readonly PaymentServiceAdapter $payment,
            private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}

        /**
         * Создание заказа на изготовление/доставку мебели.
         */
        public function createFurnitureOrder(int $tenantId, array $items, array $data, string $correlationId = ""): FurnitureOrder
        {
            $correlationId = $correlationId ?: (string) Str::uuid();

            // 1. Rate Limiting - защита от спама заказами мебели
            if ($this->rateLimiter->tooManyAttempts("furniture:order:{$tenantId}", 5)) {
                throw new \RuntimeException("Слишком много заказов. Подождите.", 429);
            }
            $this->rateLimiter->hit("furniture:order:{$tenantId}", 3600);

            return $this->db->transaction(function () use ($tenantId, $items, $data, $correlationId) {

                // 2. Fraud Check - проверка на аномально крупные заказы или частые смены адреса
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

                if ($fraud["decision"] === "block") {
                    $this->logger->error("Furniture Security Block", ["tenant_id" => $tenantId, "score" => $fraud["score"]]);
                    throw new \RuntimeException("Операция заблокирована системой безопасности.", 403);
                }

                // 3. Создание заказа
                $order = FurnitureOrder::create([
                    "uuid" => (string) Str::uuid(),
                    "tenant_id" => $tenantId,
                    "client_id" => $this->guard->id(),
                    "status" => "pending_payment",
                    "total_price_kopecks" => $data["total_price_kopecks"] ?? 0,
                    "address" => $data["address"],
                    "correlation_id" => $correlationId,
                    "tags" => ["is_custom:" . ($data["is_custom"] ? "yes" : "no")]
                ]);

                // 4. Резервация материалов или готовых позиций
                foreach ($items as $item) {
                    $this->inventory->reserveStock(
                        itemId: $item["id"],
                        quantity: $item["qty"],
                        sourceType: "furniture_order",
                        sourceId: $order->id
                    );
                }

                $this->logger->info("Furniture: order created", ["order_id" => $order->id, "corr" => $correlationId]);

                return $order;
            });
        }

        /**
         * Переход к этапу сборки (Delivery -> Assembly).
         */
        public function startAssembly(int $orderId, string $correlationId = ""): void
        {
            $correlationId = $correlationId ?: (string) Str::uuid();
            $order = FurnitureOrder::findOrFail($orderId);

            $this->db->transaction(function () use ($order, $correlationId) {
                $order->update([
                    "status" => "assembling",
                    "assembly_started_at" => Carbon::now()
                ]);

                $this->logger->info("Furniture: assembly started", ["order_id" => $order->id, "corr" => $correlationId]);
            });
        }

        /**
         * Завершение заказа. Списание остатков и выплата производителю (14% комиссия).
         */
        public function completeOrder(int $orderId, string $correlationId = ""): void
        {
            $correlationId = $correlationId ?: (string) Str::uuid();
            $order = FurnitureOrder::with("items")->findOrFail($orderId);

            $this->db->transaction(function () use ($order, $correlationId) {
                $order->update([
                    "status" => "completed",
                    "finished_at" => Carbon::now()
                ]);

                // 5. Окончательное списание из InventoryManagementService
                foreach ($order->items as $item) {
                    $this->inventory->deductStock(
                        itemId: $item->id,
                        quantity: $item->pivot->quantity ?? 1,
                        reason: "Furniture order completed: {$order->id}",
                        sourceType: "furniture_order",
                        sourceId: $order->id
                    );
                }

                // 6. Расчет комиссии платформы (14% стандарт)
                $multiplier = 0.14;
                $total = $order->total_price_kopecks;
                $platformFee = (int) ($total * $multiplier);
                $payout = $total - $platformFee;

                // Выплата производителю/магазину
                $this->wallet->credit(
                    userId: $order->tenant->owner_id, // Упрощенно
                    amount: $payout,
                    type: "furniture_payout",
                    reason: "Order finished: {$order->id}",
                    correlationId: $correlationId
                );

                $this->logger->info("Furniture: order completed + payout", ["order_id" => $order->id, "payout" => $payout]);
            });
        }
}
