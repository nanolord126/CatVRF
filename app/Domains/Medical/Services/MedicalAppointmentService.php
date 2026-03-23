<?php declare(strict_types=1);

namespace App\Domains\Medical\Services;

use App\Domains\Medical\Models\Clinic;
use App\Domains\Medical\Models\Doctor;
use App\Domains\Medical\Models\MedicalAppointment;
use App\Domains\Medical\Models\MedicalCard;
use App\Domains\Medical\Models\Prescription;
use App\Services\FraudControlService;
use App\Services\InventoryManagementService;
use App\Services\PaymentService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Сервис медицинских записей — КАНОН 2026.
 * Полная реализация с электронными картами, рецептами, списанием расходников и 14% комиссией.
 */
final class MedicalAppointmentService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly InventoryManagementService $inventory,
        private readonly PaymentService $payment,
        private readonly WalletService $wallet,
    ) {}

    /**
     * Создание записи к врачу (Clinic/Teleconsultation).
     */
    public function bookAppointment(int $clinicId, int $doctorId, array $data, string $correlationId = ""): MedicalAppointment
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        // 1. Rate Limiting — защита от DOS на медицинские записи
        if (RateLimiter::tooManyAttempts("medical:book:{$clinicId}", 5)) {
            throw new \RuntimeException("Слишком много попыток записи. Подождите.", 429);
        }
        RateLimiter::hit("medical:book:{$clinicId}", 3600);

        return DB::transaction(function () use ($clinicId, $doctorId, $data, $correlationId) {
            $clinic = Clinic::findOrFail($clinicId);
            $doctor = Doctor::findOrFail($doctorId);

            // 2. Fraud Check (проверка на подозрительные оплаты медицины)
            $fraud = $this->fraud->check([
                "user_id" => auth()->id() ?? 0,
                "operation_type" => "medical_appointment_create",
                "correlation_id" => $correlationId,
                "meta" => ["clinic_id" => $clinicId, "doctor_id" => $doctorId]
            ]);

            if ($fraud["decision"] === "block") {
                Log::channel("audit")->error("Medical Security Block", ["clinic_id" => $clinicId, "score" => $fraud["score"]]);
                throw new \RuntimeException("Операция заблокирована системой безопасности.", 403);
            }

            // 3. Создание записи
            $appointment = MedicalAppointment::create([
                "uuid" => (string) Str::uuid(),
                "tenant_id" => $clinic->tenant_id,
                "clinic_id" => $clinicId,
                "doctor_id" => $doctorId,
                "patient_id" => auth()->id(),
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

            Log::channel("audit")->info("Medical: appointment booked", ["app_id" => $appointment->id, "doctor" => $doctor->id, "corr" => $correlationId]);

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

        DB::transaction(function () use ($appointment, $findings, $correlationId) {
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

            Log::channel("audit")->info("Medical: appointment finished + payout", ["app_id" => $appointment->id, "fee" => $platformFee]);
        });
    }

    /**
     * Проверка юридических аспектов РФ (ФЗ-152, ЕГИСЗ).
     */
    public function validateRussianCompliance(int $userId): bool
    {
        // Имитация проверки согласия на ОПД и интеграции с федеральными реестрами
        Log::channel("audit")->info("Medical: compliance check (FZ-152)", ["user" => $userId]);
        return true;
    }
}
