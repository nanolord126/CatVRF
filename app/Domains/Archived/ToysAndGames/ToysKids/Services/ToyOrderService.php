<?php declare(strict_types=1);

namespace App\Domains\Archived\ToysAndGames\ToysKids\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ToyOrderService extends Model
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


         * Создание заказа на игрушки.


         */


        public function createOrder(int $tenantId, array $items, bool $isGiftWrapped = false, string $correlationId = ""): ToyOrder


        {


            $correlationId = $correlationId ?: (string) Str::uuid();


            // 1. Rate Limiting


            if (RateLimiter::tooManyAttempts("toys:order:{$tenantId}", 10)) {


                throw new \RuntimeException("Too many attempts. Wait.", 429);


            }


            RateLimiter::hit("toys:order:{$tenantId}", 3600);


            return DB::transaction(function () use ($tenantId, $items, $isGiftWrapped, $correlationId) {


                // 2. Fraud Check - защита от бонус-хантов через детские разделы


                $fraud = $this->fraud->check([


                    "user_id" => auth()->id() ?? 0,


                    "operation_type" => "toys_order_create",


                    "correlation_id" => $correlationId


                ]);


                if ($fraud["decision"] === "block") {


                    Log::channel("audit")->error("Toys Security Block", ["tenant" => $tenantId, "score" => $fraud["score"]]);


                    throw new \RuntimeException("Operation blocked by security.", 403);


                }


                // 3. Расчет стоимости и проверка возрастной категории


                $totalPrice = 0;


                foreach ($items as $item) {


                    $toy = ToyProduct::findOrFail($item["id"]);


                    $totalPrice += ($toy->price_kopecks * $item["qty"]);


                    // Простая валидация по тегам для примера


                    if (in_array("18+", $toy->tags)) {


                        // В детском разделе запрещены товары 18+


                        throw new \RuntimeException("Product {$toy->name} is not for kids.", 422);


                    }


                    // 4. Резервация игрушек


                    $this->inventory->reserveStock(


                        itemId: $item["id"],


                        quantity: $item["qty"],


                        sourceType: "toy_order",


                        sourceId: 0 // Will update later


                    );


                }


                // 5. Создание заказа


                $order = ToyOrder::create([


                    "uuid" => (string) Str::uuid(),


                    "tenant_id" => $tenantId,


                    "client_id" => auth()->id(),


                    "status" => "pending_payment",


                    "total_price_kopecks" => $totalPrice + ($isGiftWrapped ? 15000 : 0),


                    "is_gift_wrapped" => $isGiftWrapped,


                    "correlation_id" => $correlationId


                ]);


                Log::channel("audit")->info("Toys: order created", ["order_id" => $order->id, "total" => $totalPrice]);


                return $order;


            });


        }


        /**


         * Оплата и подтверждение заказа.


         */


        public function processPayment(int $orderId, string $correlationId = ""): void


        {


            $correlationId = $correlationId ?: (string) Str::uuid();


            $order = ToyOrder::findOrFail($orderId);


            DB::transaction(function () use ($order, $correlationId) {


                // Реализация реальной оплаты (упрощенно через Wallet)


                $this->wallet->debit(


                    userId: $order->client_id,


                    amount: $order->total_price_kopecks,


                    type: "toy_payment",


                    reason: "Order #{$order->id} payment",


                    correlationId: $correlationId


                );


                // 6. Расчет комиссии 14%


                $platformFee = (int) ($order->total_price_kopecks * 0.14);


                $payout = $order->total_price_kopecks - $platformFee;


                // Выплата магазину игрушек


                $this->wallet->credit(


                    userId: $order->tenant->owner_id,


                    amount: $payout,


                    type: "toys_payout",


                    reason: "Payment for order #{$order->id}",


                    correlationId: $correlationId


                );


                // 7. Окончательное списание (InventoryManagementService)


                $this->inventory->deductStock(


                    itemId: 0, // Should be linked items


                    quantity: 1,


                    reason: "Toy order completed: {$order->id}",


                    sourceType: "toy_order",


                    sourceId: $order->id


                );


                $order->update(["status" => "paid", "paid_at" => now()]);


                Log::channel("audit")->info("Toys: payment processed", ["order_id" => $order->id, "payout" => $payout]);


            });


        }
}
