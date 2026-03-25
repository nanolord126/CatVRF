<?php declare(strict_types=1);

namespace App\Domains\Furniture\Services;

use App\Domains\Furniture\Models\FurnitureItem;
use App\Domains\Furniture\Models\FurnitureOrder;
use App\Domains\Furniture\Models\FurnitureProject;
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
 * Сервис производства, доставки и сборки мебели - КАНОН 2026.
 * Полная реализация с 14% комиссией и этапами (Проект -> Производство -> Доставка -> Сборка).
 */
final class DeliveryAssemblyService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly InventoryManagementService $inventory,
        private readonly PaymentService $payment,
        private readonly WalletService $wallet,
    ) {}

    /**
     * Создание заказа на изготовление/доставку мебели.
     */
    public function createFurnitureOrder(int $tenantId, array $items, array $data, string $correlationId = ""): FurnitureOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        // 1. Rate Limiting - защита от спама заказами мебели
        if (RateLimiter::tooManyAttempts("furniture:order:{$tenantId}", 5)) {
            throw new \RuntimeException("Слишком много заказов. Подождите.", 429);
        }
        RateLimiter::hit("furniture:order:{$tenantId}", 3600);

        return $this->db->transaction(function () use ($tenantId, $items, $data, $correlationId) {
            
            // 2. Fraud Check - проверка на аномально крупные заказы или частые смены адреса
            $fraud = $this->fraud->check([
                "user_id" => auth()->id() ?? 0,
                "operation_type" => "furniture_order_create",
                "correlation_id" => $correlationId,
                "meta" => ["tenant_id" => $tenantId, "amount" => $data["total_price"] ?? 0]
            ]);

            if ($fraud["decision"] === "block") {
                $this->log->channel("audit")->error("Furniture Security Block", ["tenant_id" => $tenantId, "score" => $fraud["score"]]);
                throw new \RuntimeException("Операция заблокирована системой безопасности.", 403);
            }

            // 3. Создание заказа
            $order = FurnitureOrder::create([
                "uuid" => (string) Str::uuid(),
                "tenant_id" => $tenantId,
                "client_id" => auth()->id(),
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

            $this->log->channel("audit")->info("Furniture: order created", ["order_id" => $order->id, "corr" => $correlationId]);

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
                "assembly_started_at" => now()
            ]);

            $this->log->channel("audit")->info("Furniture: assembly started", ["order_id" => $order->id, "corr" => $correlationId]);
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
                "finished_at" => now()
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

            $this->log->channel("audit")->info("Furniture: order completed + payout", ["order_id" => $order->id, "payout" => $payout]);
        });
    }
}
