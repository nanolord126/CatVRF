<?php declare(strict_types=1);

namespace App\Domains\Electronics\Services;


use App\Domains\Payment\Services\PaymentServiceAdapter;
use App\Services\Payment\PaymentService;
use Illuminate\Cache\RateLimiter;
use Carbon\Carbon;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class WarrantyService
{


    public function __construct(private readonly FraudControlService $fraud,
            private readonly InventoryManagementService $inventory,
            private readonly PaymentServiceAdapter $payment,
            private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}

        /**
         * Создание заявки на гарантийный ремонт.
         */
        public function createWarrantyClaim(int $orderId, string $serialNumber, string $issueDescription, string $correlationId = ""): WarrantyClaim
        {
            $correlationId = $correlationId ?: (string) Str::uuid();

            // 1. Rate Limiting - защита от спама заявками
            if ($this->rateLimiter->tooManyAttempts("electronics:warranty:{$orderId}", 5)) {
                throw new \RuntimeException("Слишком много заявок. Подождите.", 429);
            }
            $this->rateLimiter->hit("electronics:warranty:{$orderId}", 3600);

            return $this->db->transaction(function () use ($orderId, $serialNumber, $issueDescription, $correlationId) {
                $order = ElectronicOrder::with("items")->findOrFail($orderId);

                // 2. Fraud Check - проверка на фиктивные возвраты и гарантии
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

                if ($fraud["decision"] === "block") {
                    $this->logger->error("Electronics Security Block", ["order_id" => $orderId, "score" => $fraud["score"]]);
                    throw new \RuntimeException("Операция заблокирована системой безопасности.", 403);
                }

                // 3. Валидация серийного номера (проверка, был ли такой продан в этом заказе)
                $itemFound = false;
                foreach ($order->items as $item) {
                    if (($item->meta["serial_number"] ?? "") === $serialNumber) {
                        $itemFound = true;
                        break;
                    }
                }

                if (!$itemFound) {
                    throw new \RuntimeException("Серийный номер не найден в заказе.", 422);
                }

                // 4. Проверка срока гарантии
                if ($order->created_at->addMonths(12)->isPast()) {
                    throw new \RuntimeException("Гарантийный срок истек (стандарт 12 мес).", 400);
                }

                // 5. Создание заявки
                $claim = WarrantyClaim::create([
                    "uuid" => (string) Str::uuid(),
                    "tenant_id" => $order->tenant_id,
                    "order_id" => $orderId,
                    "serial_number" => $serialNumber,
                    "description" => $issueDescription,
                    "status" => "pending",
                    "correlation_id" => $correlationId,
                    "tags" => ["is_warranty:yes"]
                ]);

                $this->logger->info("Electronics: warranty claim created", ["claim_id" => $claim->id, "serial" => $serialNumber, "corr" => $correlationId]);

                return $claim;
            });
        }

        /**
         * Обработка заявки (прием в СЦ).
         */
        public function acceptForRepair(int $claimId, string $correlationId = ""): void
        {
            $correlationId = $correlationId ?: (string) Str::uuid();
            $claim = WarrantyClaim::findOrFail($claimId);

            $this->db->transaction(function () use ($claim, $correlationId) {
                $claim->update([
                    "status" => "in_repair",
                    "accepted_at" => Carbon::now()
                ]);

                $this->logger->info("Electronics: claim accepted for repair", ["claim_id" => $claim->id, "corr" => $correlationId]);
            });
        }

        /**
         * Завершение ремонта / выдача товара.
         */
        public function finishRepair(int $claimId, bool $isReturnToStock = false, string $correlationId = ""): void
        {
            $correlationId = $correlationId ?: (string) Str::uuid();
            $claim = WarrantyClaim::findOrFail($claimId);

            $this->db->transaction(function () use ($claim, $isReturnToStock, $correlationId) {
                $claim->update([
                    "status" => "completed",
                    "finished_at" => Carbon::now()
                ]);

                // Если товар заменен или возвращен на склад
                if ($isReturnToStock) {
                    // В модели WarrantyClaim предполагается связь product_id
                    $this->inventory->addStock(
                        itemId: $claim->product_id ?? 0,
                        quantity: 1,
                        reason: "Warranty replacement / return to stock",
                        sourceType: "electronics_warranty",
                        sourceId: $claim->id
                    );
                }

                $this->logger->info("Electronics: warranty repair finished", ["claim_id" => $claim->id, "corr" => $correlationId]);
            });
        }
}
