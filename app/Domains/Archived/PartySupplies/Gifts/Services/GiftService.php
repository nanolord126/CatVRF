<?php declare(strict_types=1);

namespace App\Domains\Archived\PartySupplies\Gifts\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class GiftService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(


            private readonly FraudControlService $fraud,


            private readonly WalletService $wallet,


        ) {}


        /**


         * Создание заказа на подарок с индивидуальной упаковкой.


         */


        public function orderGift(int $clientId, array $data, string $correlationId = ""): GiftOrder


        {


            $correlationId = $correlationId ?: (string) Str::uuid();


            $gift = Gift::findOrFail($data["gift_id"]);


            if (RateLimiter::tooManyAttempts("gifts:order:".$clientId, 3)) {


                throw new \RuntimeException("Rate limit exceeded for gift orders.", 429);


            }


            RateLimiter::hit("gifts:order:".$clientId, 3600);


            return DB::transaction(function () use ($clientId, $gift, $data, $correlationId) {


                $this->fraud->check([


                    "user_id" => $clientId,


                    "operation_type" => "gift_order",


                    "correlation_id" => $correlationId


                ]);


                $fee = (int) ($data["total_amount"] * 0.14);


                $payout = $data["total_amount"] - $fee;


                // Списание с кошелька клиента


                $this->wallet->debit($clientId, $data["total_amount"], "gift_purchase", "Gift: {$gift->name}", $correlationId);


                // Кредит поставщику (14% комиссия)


                $this->wallet->credit($gift->tenant_id, $payout, "gift_sale_payout", "Sale #{$gift->uuid}", $correlationId);


                $order = GiftOrder::create([


                    "uuid" => (string) Str::uuid(),


                    "tenant_id" => auth()->user()->tenant_id ?? 1,


                    "client_id" => $clientId,


                    "gift_id" => $gift->id,


                    "wrapping_id" => $data["wrapping_id"] ?? null,


                    "recipient_name" => $data["recipient_name"],


                    "is_anonymous" => $data["is_anonymous"] ?? false,


                    "status" => "processing",


                    "correlation_id" => $correlationId,


                    "tags" => ["vertical:gifts", "delivery_type:surprise"]


                ]);


                Log::channel("audit")->info("Gifts: gift ordered", ["order_uuid" => $order->uuid, "payout" => $payout]);


                return $order;


            });


        }


        /**


         * Добавление праздничной упаковки к заказу.


         */


        public function addWrapping(int $orderId, int $wrappingId, string $correlationId = ""): void


        {


            $correlationId = $correlationId ?: (string) Str::uuid();


            $order = GiftOrder::findOrFail($orderId);


            $wrapping = Wrapping::findOrFail($wrappingId);


            DB::transaction(function () use ($order, $wrapping, $correlationId) {


                $order->update(["wrapping_id" => $wrappingId]);


                // Списание доп. стоимости упаковки (упрощенно)


                if ($wrapping->price > 0) {


                   $this->wallet->debit($order->client_id, $wrapping->price, "gift_wrapping", "Wrapping for #{$order->uuid}", $correlationId);


                   $this->wallet->credit($order->tenant_id, (int)($wrapping->price * 0.86), "gift_wrapping_payout", "Wrapping payout", $correlationId);


                }


                Log::channel("audit")->info("Gifts: wrapping added to product", ["order_id" => $orderId, "wrapping_id" => $wrappingId]);


            });


        }


        /**


         * Подбор подарка по поводу и бюджету (Recommender hook).


         */


        public function suggestGifts(array $filters): array


        {


            $query = Gift::query();


            if (isset($filters["max_price"])) {


                $query->where("price_kopecks", "<=", $filters["max_price"]);


            }


            if (isset($filters["occasion"])) {


               $query->whereJsonContains("tags", $filters["occasion"]);


            }


            $results = $query->limit(10)->get();


            Log::channel("audit")->info("Gifts: search suggestions generated", ["count" => $results->count()]);


            return $results->toArray();


        }
}
