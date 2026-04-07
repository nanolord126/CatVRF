<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Services;




use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class QualityControlService
{

    public function __construct(private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}

        /**
         * Проверка маркировки Честный ЗНАК и валидация партии.
         */
        public function verifyMedicineBatch(int $medicineId, string $batchNumber, string $dataMatrix, string $correlationId = ""): array
        {
            $correlationId = $correlationId ?: (string) Str::uuid();

            // 1. Rate Limiting на запросы к внешним API маркировки
            if ($this->rateLimiter->tooManyAttempts("quality:check:batch", 100)) {
                throw new \RuntimeException("External marking service bottleneck. Try later.", 429);
            }
            $this->rateLimiter->hit("quality:check:batch", 60);

            return $this->db->transaction(function () use ($medicineId, $batchNumber, $dataMatrix, $correlationId) {
                $medicine = Medicine::findOrFail($medicineId);

                // 2. Fraud Check - проверка на поддельные коды маркировки
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

                // Имитация интеграции с ГИС МТ (Честный ЗНАК)
                $isValid = $this->simulateGisMtCheck($dataMatrix);

                $check = QualityCheck::create([
                    "uuid" => (string) Str::uuid(),
                    "medicine_id" => $medicineId,
                    "batch_number" => $batchNumber,
                    "status" => $isValid ? "verified" : "rejected",
                    "verified_at" => now(),
                    "correlation_id" => $correlationId,
                    "tags" => ["gis_mt:checked", "result:" . ($isValid ? "pass" : "fail")]
                ]);

                $this->logger->info("Pharmacy: quality check completed", [
                    "check_id" => $check->id,
                    "medicine" => $medicine->name,
                    "status" => $check->status
                ]);

                return [
                    "is_valid" => $isValid,
                    "check_id" => $check->id,
                    "details" => "Batch $batchNumber verified via GIS MT"
                ];
            });
        }

        /**
         * Валидация соблюдения холодной цепи при доставке.
         */
        public function validateColdChain(int $orderId, array $temperatureLogs, string $correlationId = ""): bool
        {
            $correlationId = $correlationId ?: (string) Str::uuid();
            $order = PharmacyOrder::findOrFail($orderId);

            if (!$order->requires_cold_chain) {
                return true;
            }

            // Проверка логов температуры (должна быть в диапазоне +2..+8 C)
            $violations = array_filter($temperatureLogs, fn($t) => $t < 2.0 || $t > 8.0);
            $isValid = empty($violations);

            QualityCheck::create([
                "uuid" => (string) Str::uuid(),
                "source_type" => "pharmacy_order",
                "source_id" => $orderId,
                "type" => "cold_chain",
                "status" => $isValid ? "passed" : "violated",
                "meta" => ["logs" => $temperatureLogs, "violations" => array_values($violations)],
                "correlation_id" => $correlationId
            ]);

            if (!$isValid) {
                $this->logger->warning("Pharmacy: cold chain violation!", [
                    "order_id" => $orderId,
                    "violations" => $violations
                ]);

                // Если цепь нарушена - заказ должен быть утилизирован/возвращен
                $order->update(["status" => "rejected_by_quality_control"]);
            }

            return $isValid;
        }

        private function simulateGisMtCheck(string $matrix): bool
        {
            // В продакшене здесь будет HTTP клиент к API Честный ЗНАК
            return Str::startsWith($matrix, "01") && strlen($matrix) > 20;
        }
}
