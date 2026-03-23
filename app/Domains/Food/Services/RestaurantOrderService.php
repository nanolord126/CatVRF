<?php declare(strict_types=1);

namespace App\Domains\Food\Services;

use App\Domains\Food\Models\Restaurant;
use App\Domains\Food\Models\Dish;
use App\Domains\Food\Models\RestaurantOrder;
use App\Domains\Food\Models\RestaurantTable;
use App\Domains\Food\Models\KDSOrder;
use App\Services\FraudControlService;
use App\Services\InventoryManagementService;
use App\Services\PaymentService;
use App\Services\WalletService;
use App\Services\DemandForecastService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Сервис заказов ресторанов — КАНОН 2026.
 * Полная реализация с KDS, списанием ингредиентов, QR-заказами и 14% комиссией.
 */
final class RestaurantOrderService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly InventoryManagementService $inventory,
        private readonly PaymentService $payment,
        private readonly WalletService $wallet,
        private readonly DemandForecastService $forecast,
    ) {}

    /**
     * Создание заказа в ресторане (QR-столик или доставка).
     */
    public function createOrder(int $restaurantId, array $items, ?int $tableId = null, string $correlationId = ""): RestaurantOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        // 1. Rate Limiting — защита от DOS на заказы
        if (RateLimiter::tooManyAttempts("food:order:{$restaurantId}", 10)) {
            throw new \RuntimeException("Слишком много попыток заказа. Подождите.", 429);
        }
        RateLimiter::hit("food:order:{$restaurantId}", 3600);

        return DB::transaction(function () use ($restaurantId, $items, $tableId, $correlationId) {
            $restaurant = Restaurant::findOrFail($restaurantId);

            // 2. Fraud Check (проверка на подозрительные оплаты еды)
            $fraud = $this->fraud->check([
                "user_id" => auth()->id() ?? 0,
                "operation_type" => "food_order_create",
                "correlation_id" => $correlationId,
                "meta" => ["restaurant_id" => $restaurantId, "items_count" => count($items)]
            ]);

            if ($fraud["decision"] === "block") {
                Log::channel("audit")->error("Food Security Block", ["restaurant_id" => $restaurantId, "score" => $fraud["score"]]);
                throw new \RuntimeException("Операция заблокирована системой безопасности.", 403);
            }

            // 3. Создание заказа
            $order = RestaurantOrder::create([
                "uuid" => (string) Str::uuid(),
                "tenant_id" => $restaurant->tenant_id,
                "restaurant_id" => $restaurantId,
                "table_id" => $tableId,
                "status" => "pending",
                "total_price_kopecks" => 0,
                "correlation_id" => $correlationId
            ]);

            $totalPrice = 0;
            foreach ($items as $item) {
                $dish = Dish::findOrFail($item["id"]);
                $totalPrice += $dish->price_kopecks * $item["quantity"];

                // 4. Списание ингредиентов (InventoryManagementService)
                if ($dish->consumables_json) {
                    foreach ($dish->consumables_json as $ingredientId => $qty) {
                        $this->inventory->deductStock(
                            itemId: $ingredientId,
                            quantity: $qty * $item["quantity"],
                            reason: "Order processed: {$order->id}",
                            sourceType: "food_order",
                            sourceId: $order->id
                        );
                    }
                }
            }

            $order->update(["total_price_kopecks" => $totalPrice]);

            // 5. Передача в KDS (Kitchen Display System)
            KDSOrder::create([
                "order_id" => $order->id,
                "priority" => "normal",
                "status" => "pending",
                "estimated_minutes" => 25
            ]);

            Log::channel("audit")->info("Food: order created", ["order_id" => $order->id, "total" => $totalPrice, "corr" => $correlationId]);

            return $order;
        });
    }

    /**
     * Завершение заказа и расчет комиссии (14%).
     */
    public function completeOrder(int $orderId, string $correlationId = ""): void
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $order = RestaurantOrder::with("restaurant")->findOrFail($orderId);

        DB::transaction(function () use ($order, $correlationId) {
            $order->update([
                "status" => "delivered",
                "completed_at" => now()
            ]);

            // 6. Расчет комиссии платформы (14%)
            $total = $order->total_price_kopecks;
            $platformFee = (int) ($total * 0.14);
            $restaurantPayout = $total - $platformFee;

            // Выплата ресторану
            $this->wallet->credit(
                userId: $order->restaurant->owner_id, 
                amount: $restaurantPayout, 
                type: "food_payout", 
                reason: "Order completed: {$order->id}",
                correlationId: $correlationId
            );

            Log::channel("audit")->info("Food: payout completed", ["order_id" => $order->id, "payout" => $restaurantPayout, "fee" => $platformFee]);
        });
    }

    /**
     * Прогноз спроса на блюда на завтра.
     */
    public function predictDishDemand(int $restaurantId): array
    {
        Log::channel("audit")->info("Food: predicting dish demand", ["restaurant" => $restaurantId]);
        
        // Вызов DemandForecastService для планирования закупки продуктов
        return [
            "burgers" => $this->forecast->forecastBulk([1, 2, 3], now(), now()->addDay()),
            "sushi" => $this->forecast->forecastBulk([10, 11, 12], now(), now()->addDay())
        ];
    }
}
