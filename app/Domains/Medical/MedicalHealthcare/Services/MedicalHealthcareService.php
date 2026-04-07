<?php declare(strict_types=1);

namespace App\Domains\Medical\MedicalHealthcare\Services;




use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
final readonly class MedicalHealthcareService
{

    public function __construct(private readonly FraudControlService $fraud,
            private readonly Clinic $clinicModel,
            private readonly Doctor $doctorModel,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly Request $request, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        public function createClinic(array $data): Clinic
        {
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use ($data) {
                $clinic = $this->clinicModel->create($data);
                $this->logger->info('Клиника создана', [
                    'clinic_id' => $clinic->id,
                    'correlation_id' => $data['correlation_id'] ?? null,
                ]);
                return $clinic;
            });
        }

        public function scheduleAppointment(array $data): MedicalAppointment
        {
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use ($data) {
                $appointment = MedicalAppointment::create($data);
                $this->logger->info('Прием назначен', [
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
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use ($appointmentId) {
                $appointment = MedicalAppointment::findOrFail($appointmentId);
                $appointment->update(['status' => 'completed']);
                $this->logger->info('Прием завершён', ['appointment_id' => $appointmentId]);
                return true;
            });
        }

        public function cancelAppointment(int $appointmentId, string $reason): bool
        {
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use ($appointmentId, $reason) {
                $appointment = MedicalAppointment::findOrFail($appointmentId);
                $appointment->update(['status' => 'cancelled', 'cancellation_reason' => $reason]);
                $this->logger->warning('Прием отменён', [
                    'appointment_id' => $appointmentId,
                    'reason' => $reason,
                    'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return true;
            });
        }
}
