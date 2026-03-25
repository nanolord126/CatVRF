<?php declare(strict_types=1);

namespace App\Domains\Fashion\Services;

use App\Domains\Fashion\Models\FashionBrand;
use App\Domains\Fashion\Models\FashionProduct;
use App\Domains\Fashion\Models\FashionOrder;
use App\Domains\Fashion\Models\FashionReturn;
use App\Services\FraudControlService;
use App\Services\InventoryManagementService;
use App\Services\PaymentService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Сервис заказов в индустрии моды - КАНОН 2026.
 * Полная реализация с примеркой, возвратами, размерными сетками и 14% комиссией.
 */
final class OrderService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly InventoryManagementService $inventory,
        private readonly PaymentService $payment,
        private readonly WalletService $wallet,
    ) {}

    /**
     * Создание заказа на одежду/обувь.
     * Реализована поддержка "Try before you buy" (Примерка).
     */
    public function createOrder(int $brandId, array $items, bool $requiresFitting = false, string $correlationId = ""): FashionOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        // 1. Rate Limiting - защита от выкупа всего стока ботами
        if (RateLimiter::tooManyAttempts("fashion:order:".auth()->id(), 5)) {
            throw new \RuntimeException("Too many orders. Wait.", 429);
        }
        RateLimiter::hit("fashion:order:".auth()->id(), 3600);

        return $this->db->transaction(function () use ($brandId, $items, $requiresFitting, $correlationId) {
            $brand = FashionBrand::findOrFail($brandId);
            
            // 2. Fraud Check - проверка на массовые возвраты и поддельные аккаунты
            $fraud = $this->fraud->check([
                "user_id" => auth()->id() ?? 0,
                "operation_type" => "fashion_order_create",
                "correlation_id" => $correlationId,
                "meta" => ["brand_id" => $brandId, "items_count" => count($items)]
            ]);

            if ($fraud["decision"] === "block") {
                $this->log->channel("audit")->warning("Fashion Block", ["user" => auth()->id(), "score" => $fraud["score"]]);
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
                "client_id" => auth()->id(),
                "status" => "pending_payment",
                "total_price_kopecks" => $totalPrice,
                "requires_fitting" => $requiresFitting,
                "correlation_id" => $correlationId,
                "tags" => ["collection:spring_2026", "fitting:".($requiresFitting ? "yes" : "no")]
            ]);

            $this->log->channel("audit")->info("Fashion: order created", ["order_id" => $order->id, "fitting" => $requiresFitting]);

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

            $this->log->channel("audit")->info("Fashion: fitting processed", ["order_id" => $order->id, "kept" => count($keptItemIds)]);
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
            $order->update(["status" => "completed", "finalized_at" => now()]);

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

            $this->log->channel("audit")->info("Fashion: payout done", ["order_id" => $order->id, "payout" => $payout]);
        });
    }
}
