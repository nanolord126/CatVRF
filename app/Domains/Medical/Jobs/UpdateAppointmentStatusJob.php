<?php declare(strict_types=1);

namespace App\Domains\Medical\Jobs;



use Psr\Log\LoggerInterface;
use App\Domains\Medical\Models\MedicalAppointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class UpdateAppointmentStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $appointmentId,
        private readonly string $status,
        private readonly string $correlationId, private readonly LoggerInterface $logger) {
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

            $this->logger->info('Appointment status updated via job', [
                'appointment_id' => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'new_status' => $this->status,
                'correlation_id' => $this->correlationId,
            ]);

        } catch (Throwable $e) {
            $this->logger->error('Failed to update appointment status via job', [
                'appointment_id' => $this->appointmentId,
                'status' => $this->status,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            throw $e;
        }
    }
    
    public function retryUntil(): \DateTime
    {
        return now()->addHours(4)->toDateTime();
    }
}
