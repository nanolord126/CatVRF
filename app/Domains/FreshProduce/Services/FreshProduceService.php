<?php declare(strict_types=1);

namespace App\Domains\FreshProduce\Services;

use App\Domains\FreshProduce\Models\FreshProduct;
use App\Domains\FreshProduce\Models\FarmSupplier;
use App\Domains\FreshProduce\Models\ProduceBox;
use App\Domains\FreshProduce\Models\ProduceOrder;
use App\Services\FraudControlService;
use App\Services\InventoryManagementService;
use App\Services\WalletService;
use App\Services\DemandForecastService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Сервис доставки свежих продуктов и фермерских боксов - КАНОН 2026.
 * Контроль свежести, подписки, фермерские выплаты с 14% комиссией.
 */
final class FreshProduceService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly InventoryManagementService $inventory,
        private readonly WalletService $wallet,
        private readonly DemandForecastService $forecast,
    ) {}

    /**
     * Создание подписки на еженедельный бокс овощей/фруктов.
     */
    public function subscribeToBox(int $supplierId, string $boxType, string $correlationId = ""): ProduceBox
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($supplierId, $boxType, $correlationId) {
            $supplier = FarmSupplier::findOrFail($supplierId);
            
            // 1. Fraud Check - проверка на мультиаккаунтинг для получения скидок
            $this->fraud->check([
                "user_id" => auth()->id(),
                "operation_type" => "fresh_subscription",
                "correlation_id" => $correlationId
            ]);

            // 2. Прогноз спроса для планирования закупки у фермера
            $this->forecast->forecastBulk(
                itemIds: [$supplierId],
                dateFrom: now(),
                dateTo: now()->addMonth()
            );

            $box = ProduceBox::create([
                "uuid" => (string) Str::uuid(),
                "tenant_id" => $supplier->tenant_id,
                "supplier_id" => $supplierId,
                "user_id" => auth()->id(),
                "type" => $boxType,
                "status" => "active",
                "next_delivery_at" => now()->addWeek()->startOfDay(),
                "correlation_id" => $correlationId,
                "tags" => ["fresh", "organic", "subscription"]
            ]);

            $this->log->channel("audit")->info("Fresh: subscription created", ["box_id" => $box->id, "supplier" => $supplierId]);

            return $box;
        });
    }

    /**
     * Формирование и отправка заказа (свежая продукция).
     */
    public function processDailyDelivery(int $boxId, string $correlationId = ""): void
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $box = ProduceBox::with("supplier")->findOrFail($boxId);

        $this->db->transaction(function () use ($box, $correlationId) {
            // 3. Проверка остатков (Inventory)
            $stock = $this->inventory->getCurrentStock($box->supplier_id);
            if ($stock <= 0) {
                $this->log->channel("audit")->error("Fresh: out of stock", ["supplier" => $box->supplier_id]);
                throw new \RuntimeException("Supplier out of stock for daily delivery.");
            }

            // 4. Списание остатков
            $this->inventory->deductStock(
                itemId: $box->supplier_id,
                quantity: 1,
                reason: "Subscription delivery for Box #{$box->id}",
                sourceType: "produce_box",
                sourceId: $box->id
            );

            // 5. Создание транзакционного заказа
            ProduceOrder::create([
                "box_id" => $box->id,
                "status" => "delivered",
                "delivered_at" => now(),
                "correlation_id" => $correlationId
            ]);

            // 6. Выплата фермеру (14% комиссия платформы)
            $totalAmount = 250000; // Пример: 2500 руб в копейках
            $fee = (int) ($totalAmount * 0.14);
            $payout = $totalAmount - $fee;

            $this->wallet->credit(
                userId: $box->supplier->owner_id,
                amount: $payout,
                type: "farm_payout",
                reason: "Delivery confirmed for Box #{$box->id}",
                correlationId: $correlationId
            );

            $this->log->channel("audit")->info("Fresh: delivery processed", ["box_id" => $box->id, "payout" => $payout]);
        });
    }

    /**
     * Контроль срока годности (Expiration Check).
     */
    public function checkExpiryAlerts(int $tenantId): void
    {
        $expiredItems = FreshProduct::where("tenant_id", $tenantId)
            ->where("expiry_date", "<", now()->addDays(2))
            ->get();

        foreach ($expiredItems as $item) {
            $this->log->channel("audit")->warning("Fresh: item expiring soon", [
                "item_id" => $item->id,
                "expiry" => $item->expiry_date
            ]);
            
            // Автоматическое снижение цены или уведомление
            $item->update(["tags" => array_merge($item->tags ?? [], ["status:expiring_soon"])]);
        }
    }
}
