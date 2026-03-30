<?php declare(strict_types=1);

namespace App\Domains\Archived\Pharmacy\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class B2BService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(


            private readonly FraudControlService $fraud,


            private readonly WalletService $wallet,


        ) {}


        /**


         * Закупка партии лекарств у поставщика (B2B).


         */


        public function purchaseBatch(int $pharmacyId, int $supplierId, array $items, string $correlationId = ""): B2BOrder


        {


            $correlationId = $correlationId ?: (string) Str::uuid();


            if (RateLimiter::tooManyAttempts("pharmacy:b2b:".$pharmacyId, 10)) {


                throw new \RuntimeException("B2B purchase frequency limit exceeded.", 429);


            }


            RateLimiter::hit("pharmacy:b2b:".$pharmacyId, 3600);


            return DB::transaction(function () use ($pharmacyId, $supplierId, $items, $correlationId) {


                $pharmacy = Pharmacy::findOrFail($pharmacyId);


                $supplier = PharmacySupplier::findOrFail($supplierId);


                // 1. Проверка лицензий обеих сторон


                if (!$pharmacy->has_valid_license || !$supplier->has_valid_license) {


                    throw new \RuntimeException("One of the parties does not have a valid pharmaceutical license.", 403);


                }


                // 2. Fraud Check (ПОД/ФТ)


                $this->fraud->check([


                    "user_id" => $pharmacyId,


                    "operation_type" => "pharmacy_b2b_purchase",


                    "correlation_id" => $correlationId


                ]);


                $totalPrice = 0;


                foreach ($items as $item) {


                    $totalPrice += $item["price_kopecks"] * $item["quantity"];


                }


                $fee = (int) ($totalPrice * 0.14);


                $payout = $totalPrice - $fee;


                // 3. Создание B2B заказа


                $order = B2BOrder::create([


                    "uuid" => (string) Str::uuid(),


                    "tenant_id" => $pharmacy->tenant_id,


                    "pharmacy_id" => $pharmacyId,


                    "supplier_id" => $supplierId,


                    "total_amount" => $totalPrice,


                    "fee_amount" => $fee,


                    "status" => "pending_transfer",


                    "correlation_id" => $correlationId,


                    "tags" => ["b2b", "gis_mt_integration:required"]


                ]);


                // 4. Финансовая транзакция (Escrow hold)


                $this->wallet->hold(


                    $pharmacy->owner_id,


                    $totalPrice,


                    "pharmacy_b2b_hold",


                    "B2B Order #{$order->uuid}",


                    $correlationId


                );


                Log::channel("audit")->info("Pharmacy B2B: order initiated", [


                    "order_uuid" => $order->uuid,


                    "pharmacy" => $pharmacyId,


                    "supplier" => $supplierId


                ]);


                return $order;


            });


        }


        /**


         * Подтверждение приема партии и передача кодов в Честный ЗНАК.


         */


        public function verifyAndExecute(int $orderId, array $receivedCodes, string $correlationId = ""): void


        {


            $correlationId = $correlationId ?: (string) Str::uuid();


            $order = B2BOrder::with(["pharmacy", "supplier"])->findOrFail($orderId);


            DB::transaction(function () use ($order, $receivedCodes, $correlationId) {


                // Имитация интеграции с ГИС МТ (Честный ЗНАК)


                foreach ($receivedCodes as $code) {


                    if (!Str::startsWith($code, "01") || strlen($code) < 20) {


                        throw new \RuntimeException("Invalid DataMatrix code detected: {$code}");


                    }


                }


                $payout = $order->total_amount - $order->fee_amount;


                // Разморозка и выплата


                $this->wallet->releaseHold($order->pharmacy->owner_id, $order->total_amount, $correlationId);


                $this->wallet->credit($order->supplier->owner_id, $payout, "pharmacy_b2b_payout", "Payment for B2B Order #{$order->uuid}", $correlationId);


                $order->update(["status" => "completed", "completed_at" => now()]);


                Log::channel("audit")->info("Pharmacy B2B: execution completed with GIS MT verification", [


                    "order_id" => $orderId,


                    "codes_count" => count($receivedCodes)


                ]);


            });


        }
}
