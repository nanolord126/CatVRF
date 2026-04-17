<?php declare(strict_types=1);

namespace App\Domains\Pet\Services;

use App\Domains\Payment\Services\PaymentServiceAdapter;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;

final readonly class VetService
{
    public function __construct(private readonly FraudControlService $fraud,
            private readonly InventoryManagementService $inventory,
            private readonly PaymentServiceAdapter $payment,
            private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}

        /**
         * Запись питомца на приём.
         */
        public function bookPetAppointment(int $userId, int $vetId, int $serviceId, Carbon $dateTime, string $correlationId = ""): PetAppointment
        {
            $correlationId = $correlationId ?: (string) Str::uuid();

            // 1. Rate Limiting
            if ($this->rateLimiter->tooManyAttempts("pet:booking:{$userId}", 3)) {
                throw new \RuntimeException("Слишком много записей в ветклинику. Попробуйте позже.", 429);
            }
            $this->rateLimiter->hit("pet:booking:{$userId}", 3600);

            return $this->db->transaction(function () use ($userId, $vetId, $serviceId, $dateTime, $correlationId) {
                $vet = Vet::findOrFail($vetId);
                $clinic = PetClinic::findOrFail($vet->clinic_id);

                // 2. Fraud Check
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

                if ($fraud["decision"] === "block") {
                     throw new \RuntimeException("Запись заблокирована по соображениям безопасности.", 403);
                }

                // 3. Создание записи
                $appointment = PetAppointment::create([
                    "uuid" => (string) Str::uuid(),
                    "tenant_id" => $clinic->tenant_id,
                    "business_group_id" => $clinic->business_group_id,
                    "user_id" => $userId,
                    "vet_id" => $vetId,
                    "service_id" => $serviceId,
                    "appointment_at" => $dateTime,
                    "status" => "scheduled",
                    "correlation_id" => $correlationId,
                    "tags" => ["vertical:pet", "vet_specialization:" . $vet->specialization]
                ]);

                $this->logger->info("Pet: appointment created", ["appointment_id" => $appointment->id, "corr" => $correlationId]);

                return $appointment;
            });
        }

        /**
         * Завершение приема и списание медикаментов/корма (КАНОН).
         */
        public function completeAppointment(int $appointmentId, array $findings, ?string $vaccineId = null, string $correlationId = ""): void
        {
            $correlationId = $correlationId ?: (string) Str::uuid();
            $appointment = PetAppointment::findOrFail($appointmentId);

            $this->db->transaction(function () use ($appointment, $findings, $vaccineId, $correlationId) {
                $appointment->update(["status" => "completed"]);

                // Списание медикаментов/расходников
                if ($vaccineId) {
                    $this->inventory->deductStock(
                        itemId: (int)$vaccineId,
                        quantity: 1,
                        reason: "Vaccination: {$appointment->id}",
                        sourceType: "vet_appointment",
                        sourceId: $appointment->id,
                        correlationId: $correlationId
                    );
                }

                // Обновление электронной веткарты
                PetMedicalRecord::create([
                    "pet_id" => $appointment->pet_id,
                    "vet_id" => $appointment->vet_id,
                    "diagnosis" => $findings["diagnosis"],
                    "treatment" => $findings["treatment"],
                    "next_vaccination_at" => $findings["next_vaccine_at"] ?? null,
                    "correlation_id" => $correlationId
                ]);

                $this->logger->info("Pet: appointment completed", ["appointment_id" => $appointment->id]);
            });
        }
}
