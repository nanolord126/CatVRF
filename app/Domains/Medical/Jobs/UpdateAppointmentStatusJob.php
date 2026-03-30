<?php declare(strict_types=1);

namespace App\Domains\Medical\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class UpdateAppointmentStatusJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable;
        use InteractsWithQueue;
        use Queueable;
        use SerializesModels;

        public function __construct(
            private readonly int $appointmentId = 0,
            private readonly string $status = '',
            private readonly string $correlationId = '',
        ) {
            $this->onQueue('default');
        }

        public function tags(): array
        {
            return ['medical', 'appointment', "appointment_{$this->appointmentId}"];
        }

        public function handle(): void
        {
            try {
                $appointment = MedicalAppointment::findOrFail($this->appointmentId);

                $updates = ['status' => $this->status, 'correlation_id' => $this->correlationId];

                if ($this->status === 'in_progress') {
                    $updates['status'] = 'in_progress';
                } elseif ($this->status === 'completed') {
                    $updates['completed_at'] = now();
                } elseif ($this->status === 'cancelled') {
                    $updates['cancelled_at'] = now();
                }

                $appointment->update($updates);

                Log::channel('audit')->info('Appointment status updated via job', [
                    'appointment_id' => $appointment->id,
                    'doctor_id' => $appointment->doctor_id,
                    'patient_id' => $appointment->patient_id,
                    'status' => $this->status,
                    'correlation_id' => $this->correlationId,
                ]);
            } catch (Throwable $e) {
                Log::channel('audit')->error('Failed to update appointment status', [
                    'appointment_id' => $this->appointmentId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);
                throw $e;
            }
        }

        public function retryUntil(): \DateTime
        {
            return now()->addHours(4);
        }
}
