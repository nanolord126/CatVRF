<?php declare(strict_types=1);

namespace App\Domains\Veterinary\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AppointmentService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private FraudControlService $fraudControl,
            private string $correlationId = ''
        ) {
        }

        private function getCorrelationId(): string
        {
            return $this->correlationId ?: Str::uuid()->toString();
        }

        /**
         * Create appointment with strict validation
         */
        public function create(array $data): VeterinaryAppointment
        {
            $correlationId = $this->getCorrelationId();

            Log::channel('audit')->info('AppointmentService: Creating appointment', [
                'data' => $data,
                'correlation_id' => $correlationId
            ]);

            $this->fraudControl->check();

            return DB::transaction(function () use ($data, $correlationId) {
                $appointment = VeterinaryAppointment::create(array_merge($data, [
                    'correlation_id' => $correlationId,
                    'status' => 'pending'
                ]));

                Log::channel('audit')->info('AppointmentService: Appointment created', [
                    'id' => $appointment->id,
                    'correlation_id' => $correlationId
                ]);

                return $appointment;
            });
        }

        /**
         * Cancel appointment with reason
         */
        public function cancel(int $id, string $reason): bool
        {
            $correlationId = $this->getCorrelationId();

            return DB::transaction(function () use ($id, $reason, $correlationId) {
                $appointment = VeterinaryAppointment::findOrFail($id);

                Log::channel('audit')->warning('AppointmentService: Cancelling appointment', [
                    'id' => $id,
                    'reason' => $reason,
                    'correlation_id' => $correlationId
                ]);

                return $appointment->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => $reason,
                    'correlation_id' => $correlationId
                ]);
            });
        }

        /**
         * Mark as completed and trigger medical record creation
         */
        public function complete(int $id): void
        {
            $correlationId = $this->getCorrelationId();

            DB::transaction(function () use ($id, $correlationId) {
                $appointment = VeterinaryAppointment::findOrFail($id);
                $appointment->update([
                    'status' => 'completed',
                    'correlation_id' => $correlationId
                ]);

                Log::channel('audit')->info('AppointmentService: Appointment completed', [
                    'id' => $id,
                    'correlation_id' => $correlationId
                ]);
            });
        }
}
