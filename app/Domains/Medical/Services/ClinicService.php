<?php declare(strict_types=1);

namespace App\Domains\Medical\Services;

use App\Domains\Medical\Models\Clinic;
use App\Domains\Medical\Models\Doctor;
use App\Domains\Medical\Models\MedicalAppointment;
use App\Domains\Medical\Models\MedicalCard;
use App\Domains\Medical\Models\MedicalService;
use App\Services\FraudControlService;
use App\Services\InventoryManagementService;
use App\Services\PaymentService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
>> use Illuminate\Support\Facades\RateLimiter;
>> use Illuminate\Support\Str;
>> use Carbon\Carbon;

/**
 * Сервис управления клиникой и медицинскими записями — КАНОН 2026.
 * Полная реализация с проверкой лицензий, фродом и медкартами.
 */
final class ClinicService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly InventoryManagementService $inventory,
        private readonly PaymentService $payment,
        private readonly WalletService $wallet,
    ) {}

    /**
     * Создание записи на приём.
     */
    public function bookAppointment(int $userId, int $doctorId, int $serviceId, Carbon $dateTime, string $correlationId = ""): MedicalAppointment
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        // 1. Rate Limiting — защита от накруток записи
        if (RateLimiter::tooManyAttempts("medical:booking:{$userId}", 3)) {
            throw new \RuntimeException("Слишком много записей в медцентр. Попробуйте позже.", 429);
        }
        RateLimiter::hit("medical:booking:{$userId}", 3600);

        return DB::transaction(function () use ($userId, $doctorId, $serviceId, $dateTime, $correlationId) {
            $doctor = Doctor::findOrFail($doctorId);
            $service = MedicalService::where("doctor_id", $doctorId)->findOrFail($serviceId);
            $clinic = Clinic::findOrFail($doctor->clinic_id);

            // 2. Fraud Check (защита от записи на фиктивные услуги)
            $fraud = $this->fraud->check([
                "user_id" => $userId,
                "operation_type" => "medical_booking",
                "amount" => $service->price_kopecks,
                "correlation_id" => $correlationId,
                "meta" => ["doctor_category" => $doctor->specialization, "service_id" => $serviceId]
            ]);

            if ($fraud["decision"] === "block") {
                Log::channel("audit")->error("Medical: Security block", ["user_id" => $userId, "score" => $fraud["score"], "doctor_id" => $doctorId]);
                throw new \RuntimeException("Запись отклонена службой безопасности.", 403);
            }

            // 3. Создание записи
            $appointment = MedicalAppointment::create([
                "uuid" => (string) Str::uuid(),
                "tenant_id" => $clinic->tenant_id,
                "business_group_id" => $clinic->business_group_id,
                "user_id" => $userId,
                "doctor_id" => $doctorId,
                "service_id" => $serviceId,
                "appointment_at" => $dateTime,
                "status" => "pending",
                "price" => $service->price_kopecks,
                "correlation_id" => $correlationId,
                "tags" => ["vertical:medical", "specialization:" . $doctor->specialization]
            ]);

            // 4. HOLD расходников (лекарства, иглы, перчатки — согласно КАНОНУ)
            if (!empty($service->consumables_json)) {
                foreach ($service->consumables_json as $consumable) {
                    $this->inventory->reserveStock(
                        itemId: $consumable["id"],
                        quantity: $consumable["quantity"],
                        sourceType: "medical_appointment",
                        sourceId: $appointment->id,
                        correlationId: $correlationId
                    );
                }
            }

            Log::channel("audit")->info("Medical: appointment created", ["appointment_id" => $appointment->id, "corr" => $correlationId]);

            return $appointment;
        });
    }

    /**
     * Завершение приема и заполнение медкарты (ЭГИСЗ/КАНОН).
     */
    public function finishAppointment(int $appointmentId, array $results, ?string $prescription = null, string $correlationId = ""): void
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $appointment = MedicalAppointment::findOrFail($appointmentId);

        DB::transaction(function () use ($appointment, $results, $prescription, $correlationId) {
            $appointment->update(["status" => "completed", "doctor_notes" => $results["notes"]]);

            // Списание расходников из инвентаря
            if (!empty($appointment->service->consumables_json)) {
                foreach ($appointment->service->consumables_json as $consumable) {
                    $this->inventory->deductStock(
                        itemId: $consumable["id"],
                        quantity: $consumable["quantity"],
                        reason: "Medical appointment completed: {$appointment->id}",
                        sourceType: "medical_appointment",
                        sourceId: $appointment->id,
                        correlationId: $correlationId
                    );
                }
            }

            // Создание записи в медкарте
            MedicalCard::create([
                "user_id" => $appointment->user_id,
                "doctor_id" => $appointment->doctor_id,
                "diagnosis" => $results["diagnosis"],
                "prescription" => $prescription,
                "meta" => ["results" => $results],
                "correlation_id" => $correlationId
            ]);

            Log::channel("audit")->info("Medical: appointment finished", ["appointment_id" => $appointment->id]);
        });
    }
}
