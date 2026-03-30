<?php declare(strict_types=1);

namespace App\Domains\Pet\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PetServicesService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraudControlService,
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


                    $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
            DB::transaction(function () use ($petId, $medicationData) {
                Log::channel('audit')->info('Лекарство записано', [
                    'pet_id' => $petId,
                    'medication' => $medicationData,
                ]);
            });
        }
}
