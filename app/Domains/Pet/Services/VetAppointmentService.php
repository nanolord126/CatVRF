<?php declare(strict_types=1);

namespace App\Domains\Pet\Services;

use App\Domains\Payment\Services\PaymentServiceAdapter;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;

final readonly class VetAppointmentService
{
    public function __construct(private readonly FraudControlService $fraud,
            private readonly InventoryManagementService $inventory,
            private readonly PaymentServiceAdapter $payment,
            private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}

        /**
         * Запись питомца на прием (Клиника / Выезд на дом).
         */
        public function bookVetAppointment(int $clinicId, int $vetId, array $data, string $correlationId = ""): PetAppointment
        {
            $correlationId = $correlationId ?: (string) Str::uuid();

            // 1. Rate Limiting — защита от спама записями
            if ($this->rateLimiter->tooManyAttempts("pet:vet_book:{$clinicId}", 5)) {
                throw new \RuntimeException("Слишком много записей в эту клинику. Подождите.", 429);
            }
            $this->rateLimiter->hit("pet:vet_book:{$clinicId}", 3600);

            return $this->db->transaction(function () use ($clinicId, $vetId, $data, $correlationId) {
                $clinic = PetClinic::findOrFail($clinicId);
                $vet = Vet::findOrFail($vetId);

                // 2. Fraud Check (проверка на подозрительные оплаты ветеринарных услуг)
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

                if ($fraud["decision"] === "block") {
                    $this->logger->error("Pet Security Block", ["pet_id" => $data["pet_id"], "score" => $fraud["score"]]);
                    throw new \RuntimeException("Операция заблокирована системой безопасности.", 403);
                }

                // 3. Создание записи
                $appointment = PetAppointment::create([
                    "uuid" => (string) Str::uuid(),
                    "tenant_id" => $clinic->tenant_id,
                    "clinic_id" => $clinicId,
                    "vet_id" => $vetId,
                    "pet_id" => $data["pet_id"],
                    "appointment_at" => Carbon::parse($data["appointment_at"]),
                    "status" => "pending",
                    "price_kopecks" => $data["price"] ?? 150000,
                    "correlation_id" => $correlationId,
                    "tags" => ["type:" . ($data["type"] ?? "visit"), "emergency:" . ($data["is_emergency"] ? "yes" : "no")]
                ]);

                // 4. Резервация лекарств/расходников (InventoryManagementService)
                if (!empty($data["consumables"])) {
                    foreach ($data["consumables"] as $itemId => $qty) {
                        $this->inventory->reserveStock(
                            itemId: $itemId,
                            quantity: $qty,
                            sourceType: "vet_appointment",
                            sourceId: $appointment->id
                        );
                    }
                }

                $this->logger->info("Pet: vet appointment booked", ["app_id" => $appointment->id, "pet_id" => $data["pet_id"], "corr" => $correlationId]);

                return $appointment;
            });
        }

        /**
         * Завершение ветеринарного приема. Обновление веткарты и вакцинации.
         */
        public function completeVisit(int $appointmentId, array $results, string $correlationId = ""): void
        {
            $correlationId = $correlationId ?: (string) Str::uuid();
            $appointment = PetAppointment::with("clinic", "vet")->findOrFail($appointmentId);

            $this->db->transaction(function () use ($appointment, $results, $correlationId) {
                $appointment->update([
                    "status" => "completed",
                    "finished_at" => now()
                ]);

                // 5. Запись в ветеринарную медкарту (PetMedicalRecord)
                PetMedicalRecord::create([
                    "pet_id" => $appointment->pet_id,
                    "vet_id" => $appointment->vet_id,
                    "diagnosis" => $results["diagnosis"],
                    "treatment" => $results["treatment"],
                    "next_visit_at" => isset($results["follow_up"]) ? Carbon::parse($results["follow_up"]) : null,
                    "correlation_id" => $correlationId
                ]);

                // 6. Учет вакцин (если были)
                if (!empty($results["vaccinations"])) {
                    foreach ($results["vaccinations"] as $vaccine) {
                        // Логика добавления в ветпаспорт (симуляция)
                        $this->logger->info("Pet: vaccination recorded", ["pet" => $appointment->pet_id, "vaccine" => $vaccine["name"]]);
                    }
                }

                // 7. Списание расходников (InventoryManagementService)
                $this->inventory->deductStock(
                    itemId: 9991, // ID базового набора ветеринара
                    quantity: 1,
                    reason: "Vet visit completed",
                    sourceType: "vet_appointment",
                    sourceId: $appointment->id
                );

                // 8. Расчет комиссии платформы (14%)
                $total = $appointment->price_kopecks;
                $platformFee = (int) ($total * 0.14);
                $clinicPayout = $total - $platformFee;

                // Выплата ветклинике
                $this->wallet->credit(
                    userId: $appointment->clinic->owner_id,
                    amount: $clinicPayout,
                    type: "vet_payout",
                    reason: "Visit completed: {$appointment->id}",
                    correlationId: $correlationId
                );

                $this->logger->info("Pet: visit finished + payout", ["app_id" => $appointment->id, "payout" => $clinicPayout]);
            });
        }
}
