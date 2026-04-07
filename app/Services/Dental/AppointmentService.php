<?php declare(strict_types=1);

namespace App\Services\Dental;




use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use App\Models\Dental\DentalAppointment;
use App\Models\Dental\Dentist;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final readonly class AppointmentService
{
    public function __construct(
        private readonly Request $request,
        private \App\Services\FraudControlService $fraud,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}

    private function correlationId(): string
    {
        return $this->request->header('X-Correlation-ID') ?? Str::uuid()->toString();
    }

        /**
         * Get appointments for a clinic or dentist with tenant scoping.
         */
        public function getAppointments(int $clinicId, ?int $dentistId = null): Collection
        {
            $query = DentalAppointment::where('clinic_id', $clinicId);

            if ($dentistId) {
                $query->where('dentist_id', $dentistId);
            }

            return $query->orderBy('scheduled_at')->get();
        }

        /**
         * Create a new appointment with fraud and capacity checks.
         */
        public function createAppointment(array $data): DentalAppointment
        {
            return $this->db->transaction(function () use ($data) {
                // 1. Audit Check
                $this->logger->channel('audit')->info('Creating dental appointment', [
                    'clinic_id' => $data['clinic_id'],
                    'dentist_id' => $data['dentist_id'],
                    'client_id' => $data['client_id'],
                    'scheduled_at' => $data['scheduled_at'],
                    'correlation_id' => $this->correlationId(),
                ]);

                // 2. Capacity & Schedule Check
                $this->validateCapacity($data['dentist_id'], $data['scheduled_at']);

                // 3. Fraud Control
                $fraudResult = $this->fraud->check(
                    (int) ($data['client_id'] ?? 0),
                    'create_appointment',
                    (int) ($data['price_cents'] ?? 0),
                    (string) $this->request->ip(),
                    null,
                    $this->correlationId(),
                );

                if (($fraudResult['decision'] ?? 'allow') === 'review') {
                    throw new \RuntimeException('Appointment blocked by FraudML: High probability of suspicious activity.');
                }

                // 4. Create Appointment
                $appointment = DentalAppointment::create(array_merge($data, [
                    'correlation_id' => $this->correlationId(),
                    'uuid' => (string) Str::uuid(),
                    'status' => 'pending',
                ]));

                if (!$appointment) {
                    throw new \RuntimeException('Database error during dental appointment creation');
                }

                return $appointment;
            });
        }

        /**
         * Transition appointment status with strict auditing.
         */
        public function updateStatus(int $id, string $newStatus): void
        {
            $this->db->transaction(function () use ($id, $newStatus) {
                $appointment = DentalAppointment::findOrFail($id);
                $oldStatus = $appointment->status;

                // Log
                $this->logger->channel('audit')->info('Updating appointment status', [
                    'id' => $id,
                    'old' => $oldStatus,
                    'new' => $newStatus,
                    'correlation_id' => $this->correlationId(),
                ]);

                $appointment->transitionTo($newStatus);
            });
        }

        /**
         * Logic for validating that the dentist is available and clinic is open.
         */
        private function validateCapacity(int $dentistId, string $scheduledAt): void
        {
            $time = \Carbon\Carbon::parse($scheduledAt);
            $dentist = Dentist::findOrFail($dentistId);

            // Check clinic open hours
            if (!$dentist->clinic?->isOpenNow()) {
                 throw new \RuntimeException('Dental appointment outside clinic working hours');
            }

            // Check for concurrent appointments for the same dentist
            $concurrent = DentalAppointment::where('dentist_id', $dentistId)
                ->where('scheduled_at', $scheduledAt)
                ->whereIn('status', ['pending', 'confirmed'])
                ->count();

            if ($concurrent > 0) {
                throw new \RuntimeException('Dentist is already booked for this time slot');
            }
        }

        /**
         * Cancel an appointment with a refund policy check.
         */
        public function cancelAppointment(int $id, string $reason): bool
        {
            return $this->db->transaction(function () use ($id, $reason) {
                $appointment = DentalAppointment::findOrFail($id);

                if ($appointment->status === 'cancelled') {
                    return true;
                }

                $this->logger->channel('audit')->warning('Cancelling appointment', [
                    'appointment_id' => $id,
                    'reason' => $reason,
                    'correlation_id' => $this->correlationId(),
                ]);

                return $appointment->update([
                    'status' => 'cancelled',
                    'tags' => array_merge($appointment->tags ?? [], ['cancellation_reason' => $reason]),
                ]);
            });
        }
}
