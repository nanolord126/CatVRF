<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Services;




use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class B2BService
{

    public function __construct(private readonly FraudControlService $fraud,
            private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}

        /**
         * Закупка партии лекарств у поставщика (B2B).
         */
        public function purchaseBatch(int $pharmacyId, int $supplierId, array $items, string $correlationId = ""): B2BOrder
        {
            $correlationId = $correlationId ?: (string) Str::uuid();

            if ($this->rateLimiter->tooManyAttempts("pharmacy:b2b:".$pharmacyId, 10)) {
                throw new \RuntimeException("B2B purchase frequency limit exceeded.", 429);
            }
            $this->rateLimiter->hit("pharmacy:b2b:".$pharmacyId, 3600);

            return $this->db->transaction(function () use ($pharmacyId, $supplierId, $items, $correlationId) {
                $pharmacy = Pharmacy::findOrFail($pharmacyId);
                $supplier = PharmacySupplier::findOrFail($supplierId);

                // 1. Проверка лицензий обеих сторон
                if (!$pharmacy->has_valid_license || !$supplier->has_valid_license) {
                    throw new \RuntimeException("One of the parties does not have a valid pharmaceutical license.", 403);
                }

                // 2. Fraud Check (ПОД/ФТ)
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

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
                    \App\Domains\Wallet\Enums\BalanceTransactionType::HOLD, $correlationId, null, null, null);

                $this->logger->info("Pharmacy B2B: order initiated", [
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

            $this->db->transaction(function () use ($order, $receivedCodes, $correlationId) {
                // Имитация интеграции с ГИС МТ (Честный ЗНАК)
                foreach ($receivedCodes as $code) {
                    if (!Str::startsWith($code, "01") || strlen($code) < 20) {
                        throw new \RuntimeException("Invalid DataMatrix code detected: {$code}");
                    }
                }

                $payout = $order->total_amount - $order->fee_amount;

                // Разморозка и выплата
                $this->wallet->releaseHold($order->pharmacy->owner_id, $order->total_amount, $correlationId);
                $this->wallet->credit($order->supplier->owner_id, $payout, \App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT, $correlationId, null, null, [
                    "order_id" => $orderId,
                    "codes_count" => count($receivedCodes)
                ]);
            });
        }
}
