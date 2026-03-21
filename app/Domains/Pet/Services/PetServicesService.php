<?php declare(strict_types=1);

namespace App\Domains\Pet\Services;

use App\Domains\Pet\Models\PetClinic;
use App\Domains\Pet\Models\Vet;
use App\Domains\Pet\Models\PetAppointment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class PetServicesService
{
    public function __construct(
        private readonly PetClinic $clinicModel,
        private readonly Vet $vetModel,
    ) {}

    public function createPetClinic(array $data): PetClinic
    {
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
        return $this->vetModel
            ->where('pet_clinic_id', $clinicId)
            ->where('specialty', $specialty)
            ->where('is_available', true)
            ->get();
    }

    public function completeGroomingSession(int $appointmentId): bool
    {
        return DB::transaction(function () use ($appointmentId) {
            $appointment = PetAppointment::findOrFail($appointmentId);
            $appointment->update(['status' => 'completed']);
            Log::channel('audit')->info('Груминг завершён', ['appointment_id' => $appointmentId]);
            return true;
        });
    }

    public function recordPetMedication(int $petId, array $medicationData): void
    {
        DB::transaction(function () use ($petId, $medicationData) {
            Log::channel('audit')->info('Лекарство записано', [
                'pet_id' => $petId,
                'medication' => $medicationData,
            ]);
        });
    }
}
