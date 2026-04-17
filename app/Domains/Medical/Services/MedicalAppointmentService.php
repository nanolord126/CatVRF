<?php declare(strict_types=1);

namespace App\Domains\Medical\Services;


use App\Domains\Payment\Services\PaymentServiceAdapter;
use App\Domains\Payment\Services\PaymentService;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class MedicalAppointmentService
{


    public function __construct(private readonly FraudControlService $fraud,
            private readonly InventoryManagementService $inventory,
            private readonly PaymentServiceAdapter $payment,
            private readonly WalletService $wallet,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
        private readonly RateLimiter $rateLimiter,) {}

        /**
         * Создание записи к врачу (Clinic/Teleconsultation).
         */
        public function bookAppointment(int $clinicId, int $doctorId, array $data, string $correlationId = ""): MedicalAppointment
        {
            $correlationId = $correlationId ?: (string) Str::uuid();

            // 1. Rate Limiting — защита от DOS на медицинские записи
            if ($this->rateLimiter->tooManyAttempts("medical:book:{$clinicId}", 5)) {
                throw new \RuntimeException("Слишком много попыток записи. Подождите.", 429);
            }
            $this->rateLimiter->hit("medical:book:{$clinicId}", 3600);

            return $this->db->transaction(function () use ($clinicId, $doctorId, $data, $correlationId) {
                $clinic = Clinic::findOrFail($clinicId);
                $doctor = Doctor::findOrFail($doctorId);

                // 2. Fraud Check (проверка на подозрительные оплаты медицины)
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

                if ($fraud["decision"] === "block") {
                    $this->logger->error("Medical Security Block", ["clinic_id" => $clinicId, "score" => $fraud["score"]]);
                    throw new \RuntimeException("Операция заблокирована системой безопасности.", 403);
                }

                // 3. Создание записи
                $appointment = MedicalAppointment::create([
                    "uuid" => (string) Str::uuid(),
                    "tenant_id" => $clinic->tenant_id,
                    "clinic_id" => $clinicId,
                    "doctor_id" => $doctorId,
                    "patient_id" => $this->guard->id(),
                    "appointment_at" => Carbon::parse($data["appointment_at"]),
                    "service_type" => $data["service_type"] ?? "general",
                    "status" => "pending",
                    "price_kopecks" => $data["price"] ?? 250000,
                    "correlation_id" => $correlationId,
                    "tags" => ["telehealth:" . ($data["is_telehealth"] ? "yes" : "no")]
                ]);

                // 4. Резервация медицинских расходников (InventoryManagementService)
                if (!empty($data["consumables"])) {
                    foreach ($data["consumables"] as $itemId => $qty) {
                        $this->inventory->reserveStock(
                            itemId: $itemId,
                            quantity: $qty,
                            sourceType: "medical_appointment",
                            sourceId: $appointment->id
                        );
                    }
                }

                $this->logger->info("Medical: appointment booked", ["app_id" => $appointment->id, "doctor" => $doctor->id, "corr" => $correlationId]);

                return $appointment;
            });
        }

        /**
         * Завершение приема и выписка рецептов/записей в медкарту.
         */
        public function finishAppointment(int $appointmentId, array $findings, string $correlationId = ""): void
        {
            $correlationId = $correlationId ?: (string) Str::uuid();
            $appointment = MedicalAppointment::with("clinic", "doctor")->findOrFail($appointmentId);

            $this->db->transaction(function () use ($appointment, $findings, $correlationId) {
                $appointment->update([
                    "status" => "completed",
                    "finished_at" => now()
                ]);

                // 5. Запись в медицинскую карту (MedicalCard)
                MedicalCard::create([
                    "patient_id" => $appointment->patient_id,
                    "clinic_id" => $appointment->clinic_id,
                    "doctor_id" => $appointment->doctor_id,
                    "diagnosis" => $findings["diagnosis"],
                    "recommendations" => $findings["recommendations"],
                    "correlation_id" => $correlationId
                ]);

                // 6. Выписка рецепта (Prescription)
                if (!empty($findings["prescriptions"])) {
                    foreach ($findings["prescriptions"] as $med) {
                        Prescription::create([
                            "uuid" => (string) Str::uuid(),
                            "patient_id" => $appointment->patient_id,
                            "medicine_id" => $med["id"],
                            "dosage" => $med["dosage"],
                            "expires_at" => now()->addMonths(3),
                            "correlation_id" => $correlationId
                        ]);
                    }
                }

                // 7. Расчет комиссии платформы (14%)
                $total = $appointment->price_kopecks;
                $platformFee = (int) ($total * 0.14);
                $clinicPayout = $total - $platformFee;

                // Выплата клинике
                $this->wallet->credit(
                    userId: $appointment->clinic->owner_id,
                    amount: $clinicPayout,
                    type: "medical_payout",
                    reason: "Appointment completed: {$appointment->id}",
                    correlationId: $correlationId
                );

                $this->logger->info("Medical: appointment finished + payout", ["app_id" => $appointment->id, "user" => $this->guard->id()]);
                return true;
            });
        }
}
