<?php declare(strict_types=1);

namespace App\Domains\Jewelry\Services;

use App\Domains\Jewelry\Models\JewelryItem;
use App\Domains\Jewelry\Models\JewelryOrder;
use App\Services\FraudControlService;
use App\Services\WalletService;
use App\Services\InventoryManagementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Сервис ювелирных изделий - КАНОН 2026.
 * Эскроу, ФЗ-115, 14% комиссия, контроль остатков и RFID/ГИИС ДМДК.
 */
final class JewelryService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly WalletService $wallet,
        private readonly InventoryManagementService $inventory,
    ) {}

    /**
     * Заказ ювелирного изделия с холдированием средств (Escrow).
     */
    public function purchase(int $itemId, int $userId, int $tenantId, string $correlationId = ""): JewelryOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        if (RateLimiter::tooManyAttempts("jewelry:purchase:".$userId, 5)) {
            throw new \RuntimeException("Jewelry purchase frequency limit exceeded.", 429);
        }
        RateLimiter::hit("jewelry:purchase:".$userId, 3600);

        return DB::transaction(function () use ($itemId, $userId, $tenantId, $correlationId) {
            $item = JewelryItem::where("tenant_id", $tenantId)->findOrFail($itemId);

            // 1. Проверка ПОД/ФТ (ФЗ-115) для дорогих изделий (>600к)
            if ($item->price_kopecks >= 60000000) {
                $this->fraud->check([
                    "user_id" => $userId,
                    "operation_type" => "high_value_jewelry_purchase",
                    "amount" => $item->price_kopecks,
                    "correlation_id" => $correlationId,
                    "requires_manual_verification" => true
                ]);
            } else {
                $this->fraud->check([
                    "user_id" => $userId,
                    "operation_type" => "jewelry_purchase",
                    "correlation_id" => $correlationId
                ]);
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
                "jewelry_escrow_hold",
                "Order #{$order->uuid} for {$item->name}",
                $correlationId
            );

            Log::channel("audit")->info("Jewelry: purchase initiated (Escrow)", [
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

        DB::transaction(function () use ($order, $correlationId) {
            if ($order->status !== "awaiting_delivery") {
                throw new \RuntimeException("Order cannot be fulfilled in status: {$order->status}");
            }

            $payout = $order->amount - $order->fee_amount;

            // Списание со стока окончательное
            $this->inventory->deductStock($order->jewelry_item_id, 1, "Fulfillment of order {$order->uuid}", "jewelry_order", $order->id);

            // Разморозка и перевод вендору (тенанту)
            $this->wallet->releaseHold($order->user_id, $order->amount, $correlationId);
            $this->wallet->credit($order->tenant_id, $payout, "jewelry_sale_payout", "Payment for Jewelry Order #{$order->uuid}", $correlationId);

            $order->update(["status" => "completed", "completed_at" => now()]);

            Log::channel("audit")->info("Jewelry: order fulfilled and paid", [
                "order_id" => $orderId,
                "payout" => $payout
            ]);
        });
    }
}
