<?php declare(strict_types=1);

namespace App\Domains\MeatShops\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MeatService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraud,
            private readonly InventoryManagementService $inventory,
            private readonly WalletService $wallet,
        ) {}

        /**
         * Создание заказа на мясо/мясные наборы.
         */
        public function createOrder(int $supplierId, array $items, string $correlationId = ""): MeatOrder
        {
            $correlationId = $correlationId ?: (string) Str::uuid();

            // 1. Rate Limiting - защита от спама заказами
            if (RateLimiter::tooManyAttempts("meat:order:".auth()->id(), 3)) {
                throw new \RuntimeException("Too many orders. Wait for cooldown.", 429);
            }
            RateLimiter::hit("meat:order:".auth()->id(), 3600);

            return DB::transaction(function () use ($supplierId, $items, $correlationId) {
                $supplier = MeatSupplier::findOrFail($supplierId);

                // 2. Fraud Check - проверка поставщика и клиента
                $this->fraud->check([
                    "user_id" => auth()->id(),
                    "operation_type" => "meat_order",
                    "correlation_id" => $correlationId,
                    "meta" => ["supplier_id" => $supplierId]
                ]);

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
                    "client_id" => auth()->id(),
                    "status" => "pending_butcher", // Передано мяснику на разделку
                    "total_price_kopecks" => $totalPrice,
                    "correlation_id" => $correlationId,
                    "tags" => ["halal:yes", "fresh:premium", "vacuum_packed:yes"]
                ]);

                Log::channel("audit")->info("Meat: order sent to butcher", ["order_id" => $order->id, "total" => $totalPrice]);

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

            DB::transaction(function () use ($order, $finalWeightGrams, $correlationId) {
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

                Log::channel("audit")->info("Meat: order weighed and ready", ["order_id" => $order->id, "weight" => $finalWeightGrams]);
            });
        }

        /**
         * Выплата магазину/фермеру после подтверждения доставки (14% комиссия).
         */
        public function finalizePayout(int $orderId, string $correlationId = ""): void
        {
            $correlationId = $correlationId ?: (string) Str::uuid();
            $order = MeatOrder::with("supplier")->findOrFail($orderId);

            DB::transaction(function () use ($order, $correlationId) {
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

                $order->update(["status" => "completed", "payout_released_at" => now()]);

                Log::channel("audit")->info("Meat: final payout", ["order_id" => $order->id, "payout" => $payout]);
            });
        }
}
