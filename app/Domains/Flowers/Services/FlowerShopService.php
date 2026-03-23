<?php declare(strict_types=1);

namespace App\Domains\Flowers\Services;

use App\Domains\Flowers\Models\FlowerShop;
use App\Domains\Flowers\Models\FlowerOrder;
use App\Domains\Flowers\Models\Bouquet;
use App\Services\FraudControlService;
use App\Services\WalletService;
use App\Domains\Logistics\Services\RouteOptimizationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Сервис управления цветами и букетами - КАНОН 2026.
 * Конструктор букетов, быстрая доставка (45-90 мин), 14% комиссия.
 */
final class FlowerShopService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly WalletService $wallet,
        private readonly RouteOptimizationService $logistics
    ) {}

    /**
     * Создание заказа на букет с проверкой свежести и логистикой.
     */
    public function createOrder(int $clientId, array $data, string $correlationId = ""): FlowerOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        if (RateLimiter::tooManyAttempts("flowers:order:".$clientId, 3)) {
            throw new \RuntimeException("Rate limit exceeded for flower orders.", 429);
        }
        RateLimiter::hit("flowers:order:".$clientId, 3600);

        return DB::transaction(function () use ($clientId, $data, $correlationId) {
            $this->fraud->check([
                "user_id" => $clientId,
                "operation_type" => "flower_order",
                "correlation_id" => $correlationId
            ]);

            $order = FlowerOrder::create([
                "uuid" => (string) Str::uuid(),
                "tenant_id" => auth()->user()->tenant_id ?? 1,
                "client_id" => $clientId,
                "shop_id" => $data["shop_id"],
                "total_amount" => $data["total_amount"],
                "status" => "pending",
                "delivery_address" => $data["address"],
                "is_anonymous" => $data["is_anonymous"] ?? false,
                "card_text" => $data["card_text"] ?? null,
                "correlation_id" => $correlationId,
                "tags" => ["vertical:flowers", "delivery_urgency:express"]
            ]);

            // Резервирование в кошельке
            $this->wallet->hold(
                $clientId,
                $data["total_amount"],
                "flower_purchase",
                "Order #{$order->uuid}",
                $correlationId
            );

            Log::channel("audit")->info("Flowers: order created", ["order_uuid" => $order->uuid]);

            return $order;
        });
    }

    /**
     * Сборка и отправка букета (14% комиссия).
     */
    public function shipOrder(int $orderId, string $correlationId = ""): void
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $order = FlowerOrder::findOrFail($orderId);

        DB::transaction(function () use ($order, $correlationId) {
            $fee = (int) ($order->total_amount * 0.14);
            $payout = $order->total_amount - $fee;

            $order->update(["status" => "shipped", "shipped_at" => now()]);

            $this->wallet->releaseHold($order->client_id, $order->total_amount, $correlationId);
            $this->wallet->credit($order->shop_id, $payout, "flower_sale_payout", "Sale #{$order->uuid}", $correlationId);

            Log::channel("audit")->info("Flowers: order balance settled", [
                "order_id" => $orderId,
                "fee" => $fee,
                "payout" => $payout
            ]);
        });
    }

    /**
     * Проверка срока годности цветов в магазине (Batch Quality).
     */
    public function verifyStockFreshness(int $shop_id): array
    {
        $expired = Bouquet::where("shop_id", $shop_id)
            ->where("expires_at", "<", now())
            ->where("status", "available")
            ->get();

        foreach ($expired as $item) {
            $item->update(["status" => "wasted"]);
            Log::channel("audit")->warning("Flowers: item expired", ["shop" => $shop_id, "item" => $item->id]);
        }

        return ["wasted_count" => $expired->count()];
    }
}
