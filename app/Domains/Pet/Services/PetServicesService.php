<?php declare(strict_types=1);

namespace App\Domains\Pet\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use App\Domains\Pet\Models\PetClinic;
use App\Domains\Pet\Models\Vet;
use App\Domains\Pet\Models\PetAppointment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class PetServicesService
{
    public function __construct(
        private readonly PetClinic $clinicModel,
        private readonly Vet $vetModel,
    ) {}

    public function createPetClinic(array $data): PetClinic
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'createPetClinic'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL createPetClinic', ['domain' => __CLASS__]);

        return DB::transaction(function () use ($data) {
            $clinic = $this->clinicModel->create($data);
            Log::channel('audit')->info('Ветклиника создана', [
                'clinic_id' => $clinic->id,
                'correlation_id' => $data['correlation_id'] ?? null,
            ]);
            return $clinic;
        });
    }

    public function bookGroomingAppointment(array $data): PetAppointment
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'bookGroomingAppointment'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL bookGroomingAppointment', ['domain' => __CLASS__]);

        return DB::transaction(function () use ($data) {
            $appointment = PetAppointment::create($data);
            Log::channel('audit')->info('Запись на груминг', [
                'appointment_id' => $appointment->id,
                'correlation_id' => $data['correlation_id'] ?? null,
            ]);
            return $appointment;
        });
    }

    public function getAvailableVets(int $clinicId, string $specialty): Collection
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'getAvailableVets'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL getAvailableVets', ['domain' => __CLASS__]);

        return $this->vetModel
            ->where('pet_clinic_id', $clinicId)
            ->where('specialty', $specialty)
            ->where('is_available', true)
            ->get();
    }

    public function completeGroomingSession(int $appointmentId): bool
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'completeGroomingSession'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL completeGroomingSession', ['domain' => __CLASS__]);

        return DB::transaction(function () use ($appointmentId) {
            $appointment = PetAppointment::findOrFail($appointmentId);
            $appointment->update(['status' => 'completed']);
            Log::channel('audit')->info('Груминг завершён', ['appointment_id' => $appointmentId]);
            return true;
        });
    }

    public function recordPetMedication(int $petId, array $medicationData): void
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'recordPetMedication'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL recordPetMedication', ['domain' => __CLASS__]);

        DB::transaction(function () use ($petId, $medicationData) {
            Log::channel('audit')->info('Лекарство записано', [
                'pet_id' => $petId,
                'medication' => $medicationData,
            ]);
        });
    }
}
