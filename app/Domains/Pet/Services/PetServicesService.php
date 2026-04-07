<?php declare(strict_types=1);

namespace App\Domains\Pet\Services;




use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
final readonly class PetServicesService
{

    public function __construct(private readonly FraudControlService $fraud,
            private readonly PetClinic $clinicModel,
            private readonly Vet $vetModel,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly Request $request, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        public function createPetClinic(array $data): PetClinic
        {

            return $this->db->transaction(function () use ($data) {
                $clinic = $this->clinicModel->create($data);
                $this->logger->info('Ветклиника создана', [
                    'clinic_id' => $clinic->id,
                    'correlation_id' => $data['correlation_id'] ?? null,
                ]);
                return $clinic;
            });
        }

        public function bookGroomingAppointment(array $data): PetAppointment
        {

            return $this->db->transaction(function () use ($data) {
                $appointment = PetAppointment::create($data);
                $this->logger->info('Запись на груминг', [
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

            return $this->db->transaction(function () use ($appointmentId) {
                $appointment = PetAppointment::findOrFail($appointmentId);
                $appointment->update(['status' => 'completed']);
                $this->logger->info('Груминг завершён', ['appointment_id' => $appointmentId]);
                return true;
            });
        }

        public function recordPetMedication(int $petId, array $medicationData): void
        {

                    $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
            $this->db->transaction(function () use ($petId, $medicationData) {
                $this->logger->info('Лекарство записано', [
                    'pet_id' => $petId,
                    'medication' => $medicationData,
                    'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
            });
        }
}
