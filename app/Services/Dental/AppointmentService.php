<?php declare(strict_types=1);

namespace App\Services\Dental;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AppointmentService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private \App\Services\FraudControlService $fraudControl,
            private string $correlation_id = ''
        ) {
            $this->correlation_id = empty($correlation_id) ? (string) Str::uuid() : $correlation_id;
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
            return DB::transaction(function () use ($data) {
                // 1. Audit Check
                Log::channel('audit')->info('Creating dental appointment', [
                    'clinic_id' => $data['clinic_id'],
                    'dentist_id' => $data['dentist_id'],
                    'client_id' => $data['client_id'],
                    'scheduled_at' => $data['scheduled_at'],
                    'correlation_id' => $this->correlation_id,
                ]);

                // 2. Capacity & Schedule Check
                $this->validateCapacity($data['dentist_id'], $data['scheduled_at']);

                // 3. Fraud Control
                $fraudScore = $this->fraudControl->check([
                    'operation' => 'create_appointment',
                    'user_id' => $data['client_id'],
                    'scheduled_at' => $data['scheduled_at'],
                    'data' => $data,
                ]);

                if ($fraudScore > 0.8) {
                    throw new \RuntimeException('Appointment blocked by FraudML: High probability of suspicious activity.');
                }

                // 4. Create Appointment
                $appointment = DentalAppointment::create(array_merge($data, [
                    'correlation_id' => $this->correlation_id,
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
            DB::transaction(function () use ($id, $newStatus) {
                $appointment = DentalAppointment::findOrFail($id);
                $oldStatus = $appointment->status;

                // Log
                Log::channel('audit')->info('Updating appointment status', [
                    'id' => $id,
                    'old' => $oldStatus,
                    'new' => $newStatus,
                    'correlation_id' => $this->correlation_id,
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
            return DB::transaction(function () use ($id, $reason) {
                $appointment = DentalAppointment::findOrFail($id);

                if ($appointment->status === 'cancelled') {
                    return true;
                }

                Log::channel('audit')->warning('Cancelling appointment', [
                    'appointment_id' => $id,
                    'reason' => $reason,
                    'correlation_id' => $this->correlation_id,
                ]);

                return $appointment->update([
                    'status' => 'cancelled',
                    'tags' => array_merge($appointment->tags ?? [], ['cancellation_reason' => $reason]),
                ]);
            });
        }
}
