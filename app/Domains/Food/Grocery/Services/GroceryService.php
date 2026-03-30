<?php declare(strict_types=1);

namespace App\Domains\Food\Grocery\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class GroceryService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraud,
            private readonly InventoryManagementService $inventory,
            private readonly PaymentService $payment,
            private readonly WalletService $wallet,
        ) {}

        /**
         * Создание заказа в супермаркете.
         */
        public function createOrder(int $userId, int $storeId, array $items, string $address, string $correlationId = ""): GroceryOrder
        {
            $correlationId = $correlationId ?: (string) Str::uuid();

            // 1. Rate Limiting
            $rlKey = "grocery:order:{$userId}";
            if (RateLimiter::tooManyAttempts($rlKey, 5)) {
                Log::channel("fraud_alert")->warning("Grocery: rate limit hit", ["user_id" => $userId, "correlation_id" => $correlationId]);
                throw new \RuntimeException("Слишком много попыток заказа. Попробуйте позже.", 429);
            }
            RateLimiter::hit($rlKey, 3600);

            return DB::transaction(function () use ($userId, $storeId, $items, $address, $correlationId) {
                $store = GroceryStore::findOrFail($storeId);
                $totalAmountKopecks = 0;
                $processedItems = [];

                // 2. Валидация остатков и расчет суммы
                foreach ($items as $item) {
                    $product = GroceryProduct::where("store_id", $storeId)->where("uuid", $item["uuid"])->lockForUpdate()->firstOrFail();

                    if ($product->current_stock < $item["quantity"]) {
                        throw new \RuntimeException("Недостаточно товара: {$product->name}", 422);
                    }

                    $totalAmountKopecks += $product->price_kopecks * $item["quantity"];
                    $processedItems[] = [
                        "product_id" => $product->id,
                        "name" => $product->name,
                        "sku" => $product->sku,
                        "quantity" => $item["quantity"],
                        "price_at_purchase" => $product->price_kopecks,
                    ];
                }

                // 3. Fraud Check
                $fraud = $this->fraud->check([
                    "user_id" => $userId,
                    "operation_type" => "grocery_order_create",
                    "amount" => $totalAmountKopecks,
                    "correlation_id" => $correlationId,
                    "meta" => ["items_count" => count($items), "store_id" => $storeId]
                ]);

                if ($fraud["decision"] === "block") {
                    Log::channel("audit")->warning("Grocery: fraud block", ["user_id" => $userId, "score" => $fraud["score"], "correlation_id" => $correlationId]);
                    throw new \RuntimeException("Заказ заблокирован службой безопасности.", 403);
                }

                // 4. Создание заказа
                $order = GroceryOrder::create([
                    "tenant_id" => $store->tenant_id,
                    "business_group_id" => $store->business_group_id,
                    "user_id" => $userId,
                    "store_id" => $storeId,
                    "uuid" => (string) Str::uuid(),
                    "correlation_id" => $correlationId,
                    "status" => "pending",
                    "total_amount" => $totalAmountKopecks,
                    "delivery_address" => $address,
                    "items_json" => $processedItems,
                    "tags" => ["source:web", "vertical:grocery"]
                ]);

                // 5. Резервирование остатков
                foreach ($processedItems as $pItem) {
                    $this->inventory->reserveStock(
                        itemId: $pItem["product_id"],
                        quantity: $pItem["quantity"],
                        sourceType: "grocery_order",
                        sourceId: $order->id,
                        correlationId: $correlationId
                    );
                }

                Log::channel("audit")->info("Grocery: order created", [
                    "order_id" => $order->id,
                    "user_id" => $userId,
                    "total" => $totalAmountKopecks,
                    "correlation_id" => $correlationId
                ]);

                return $order;
            });
        }

        /**
         * Подтверждение сборки заказа.
         */
        public function finalizeOrder(int $orderId, string $correlationId = ""): void
        {
            $correlationId = $correlationId ?: (string) Str::uuid();
            $order = GroceryOrder::findOrFail($orderId);

            DB::transaction(function () use ($order, $correlationId) {
                $order->lockForUpdate();
                $order->update(["status" => "ready_for_delivery"]);

                // Списание остатков
                foreach ($order->items_json as $item) {
                    $this->inventory->deductStock(
                        itemId: $item["product_id"],
                        quantity: $item["quantity"],
                        reason: "Grocery order finalized: {$order->id}",
                        sourceType: "grocery_order",
                        sourceId: $order->id,
                        correlationId: $correlationId
                    );
                }

                Log::channel("audit")->info("Grocery: order finalized", ["order_id" => $order->id, "correlation_id" => $correlationId]);
            });
        }

        /**
         * Синхронизация остатков с торговой системой.
         */
        public function syncStock(int $storeId, array $stockData): void
        {
            $correlationId = (string) Str::uuid();

            DB::transaction(function () use ($storeId, $stockData, $correlationId) {
                foreach ($stockData as $data) {
                    $product = GroceryProduct::where("store_id", $storeId)->where("sku", $data["sku"])->first();
                    if ($product) {
                        $diff = $data["quantity"] - $product->current_stock;
                        $product->update(["current_stock" => $data["quantity"]]);

                        if ($diff != 0) {
                            $this->inventory->addStock(
                                itemId: $product->id,
                                quantity: $diff,
                                reason: "External Stock Sync",
                                sourceType: "external_sync",
                                sourceId: $storeId,
                                correlationId: $correlationId
                            );
                        }
                    }
                }
            });
        }
}
