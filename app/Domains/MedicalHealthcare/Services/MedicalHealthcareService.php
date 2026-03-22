<?php declare(strict_types=1);

namespace App\Domains\MedicalHealthcare\Services;

use App\Services\FraudControlService;
use Illuminate\Support\Facades\Log;

use App\Domains\MedicalHealthcare\Models\Clinic;
use App\Domains\MedicalHealthcare\Models\Doctor;
use App\Domains\MedicalHealthcare\Models\MedicalAppointment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class MedicalHealthcareService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
        private readonly Clinic $clinicModel,
        private readonly Doctor $doctorModel,
    ) {}

    public function createClinic(array $data): Clinic
    {
        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
DB::transaction(function () use ($data) {
            $clinic = $this->clinicModel->create($data);
            Log::channel('audit')->info('Клиника создана', [
                'clinic_id' => $clinic->id,
                'correlation_id' => $data['correlation_id'] ?? null,
            ]);
            return $clinic;
        });
    }

    public function scheduleAppointment(array $data): MedicalAppointment
    {
        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
DB::transaction(function () use ($data) {
            $appointment = MedicalAppointment::create($data);
            Log::channel('audit')->info('Прием назначен', [
                'appointment_id' => $appointment->id,
                'correlation_id' => $data['correlation_id'] ?? null,
            ]);
            return $appointment;
        });
    }

    public function getAvailableDoctors(int $clinicId, string $specialty): Collection
    {
        return $this->doctorModel
            ->where('clinic_id', $clinicId)
            ->where('specialty', $specialty)
            ->where('is_available', true)
            ->get();
    }

    public function completeAppointment(int $appointmentId): bool
    {
        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
DB::transaction(function () use ($appointmentId) {
            $appointment = MedicalAppointment::findOrFail($appointmentId);
            $appointment->update(['status' => 'completed']);
            Log::channel('audit')->info('Прием завершён', ['appointment_id' => $appointmentId]);
            return true;
        });
    }

    public function cancelAppointment(int $appointmentId, string $reason): bool
    {
        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
DB::transaction(function () use ($appointmentId, $reason) {
            $appointment = MedicalAppointment::findOrFail($appointmentId);
            $appointment->update(['status' => 'cancelled', 'cancellation_reason' => $reason]);
            Log::channel('audit')->warning('Прием отменён', [
                'appointment_id' => $appointmentId,
                'reason' => $reason,
            ]);
            return true;
        });
    }
}
