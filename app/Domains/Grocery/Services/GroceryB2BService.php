<?php declare(strict_types=1);

namespace App\Domains\Grocery\Services;

use App\Domains\Grocery\Models\GroceryStore;
use App\Domains\Grocery\Models\GroceryProduct;
use App\Domains\Grocery\Models\GroceryB2BOrder;
use App\Services\FraudControlService;
use App\Services\WalletService;
use App\Services\InventoryManagementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * B2B Сервис продуктового ритейла - КАНОН 2026.
 * Оптовые закупки, контроль свежести, 14% комиссия, Escrow.
 */
final class GroceryB2BService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly WalletService $wallet,
        private readonly InventoryManagementService $inventory,
    ) {}

    /**
     * Создание B2B заказа на поставку продуктов (опт).
     */
    public function createBulkOrder(int $storeId, int $supplierId, array $items, string $correlationId = ""): GroceryB2BOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        if (RateLimiter::tooManyAttempts("grocery:b2b:order:".$storeId, 20)) {
            throw new \RuntimeException("Grocery B2B order limit exceeded.", 429);
        }
        RateLimiter::hit("grocery:b2b:order:".$storeId, 3600);

        return DB::transaction(function () use ($storeId, $supplierId, $items, $correlationId) {
            $store = GroceryStore::findOrFail($storeId);
            
            // 1. Fraud Check (проверка лимитов и репутации)
            $this->fraud->check([
                "user_id" => $store->owner_id,
                "operation_type" => "grocery_bulk_purchase",
                "correlation_id" => $correlationId
            ]);

            $totalAmount = 0;
            foreach ($items as $item) {
                $product = GroceryProduct::findOrFail($item["product_id"]);
                $totalAmount += $product->price_kopecks * $item["quantity"];
                
                // Резервируем на складе поставщика
                $this->inventory->reserveStock($product->id, $item["quantity"], "grocery_b2b_order", 0);
            }

            $fee = (int) ($totalAmount * 0.14);

            // 2. Создание B2B заказа
            $order = GroceryB2BOrder::create([
                "uuid" => (string) Str::uuid(),
                "tenant_id" => $store->tenant_id,
                "store_id" => $storeId,
                "supplier_id" => $supplierId,
                "total_amount" => $totalAmount,
                "fee_amount" => $fee,
                "status" => "pending_delivery",
                "correlation_id" => $correlationId,
                "tags" => ["grocery_b2b", "freshness_control:required"]
            ]);

            // 3. Escrow Hold
            $this->wallet->hold(
                $store->owner_id,
                $totalAmount,
                "grocery_b2b_escrow",
                "Wholesale Order #{$order->uuid}",
                $correlationId
            );

            Log::channel("audit")->info("Grocery B2B: bulk order created", [
                "order_uuid" => $order->uuid,
                "store_id" => $storeId,
                "amount" => $totalAmount
            ]);

            return $order;
        });
    }

    /**
     * Приемка товара с проверкой качества и финальным расчетом.
     */
    public function confirmReceipt(int $orderId, array $qualityReport, string $correlationId = ""): void
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $order = GroceryB2BOrder::with(["store", "supplier"])->findOrFail($orderId);

        DB::transaction(function () use ($order, $qualityReport, $correlationId) {
            if ($order->status !== "pending_delivery") {
                throw new \RuntimeException("Order #{$order->uuid} cannot be confirmed in current status.");
            }

            // 1. Анализ качества (возврат за испорченный товар)
            $spoiledAmount = $qualityReport["spoiled_amount_kopecks"] ?? 0;
            $netAmount = $order->total_amount - $spoiledAmount;

            if ($netAmount < 0) {
                $netAmount = 0;
            }

            $fee = (int) ($netAmount * 0.14);
            $payout = $netAmount - $fee;

            // 2. Разморозка средств (Escrow Release)
            $this->wallet->releaseHold($order->store->owner_id, $order->total_amount, $correlationId);

            // 3. Выплата поставщику (за вычетом порчи и комиссии)
            if ($payout > 0) {
                $this->wallet->credit(
                    $order->supplier->owner_id,
                    $payout,
                    "grocery_b2b_payout",
                    "Payout for Order #{$order->uuid}",
                    $correlationId
                );
            }

            // Возврат остатка покупателю (за испорченный товар)
            if ($spoiledAmount > 0) {
                $this->wallet->credit(
                    $order->store->owner_id,
                    $spoiledAmount,
                    "grocery_b2b_refund",
                    "Refund for spoiled items in Order #{$order->uuid}",
                    $correlationId
                );
            }

            $order->update([
                "status" => "completed",
                "completed_at" => now(),
                "actual_amount" => $netAmount,
                "metadata" => array_merge($order->metadata ?? [], ["quality_report" => $qualityReport])
            ]);

            Log::channel("audit")->info("Grocery B2B: receipt confirmed", [
                "order_id" => $order->id,
                "spoiled" => $spoiledAmount,
                "payout" => $payout
            ]);
        });
    }
}
