<?php declare(strict_types=1);

namespace App\Domains\Pet\Jobs;

use App\Domains\Pet\Models\PetAppointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class UpdateAppointmentStatusJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        private readonly int $appointmentId = 0,
        private readonly string $newStatus = '',
        private readonly string $correlationId = '',
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        try {
            $appointment = PetAppointment::find($this->appointmentId);

            if (!$appointment) {
                Log::warning('Pet appointment not found', [
                    'appointment_id' => $this->appointmentId,
                    'correlation_id' => $this->correlationId,
                ]);
                return;
            }

            $appointment->update([
                'status' => $this->newStatus,
                'correlation_id' => $this->correlationId,
            ]);

            Log::channel('audit')->info('Pet appointment status updated', [
                'appointment_id' => $appointment->id,
                'clinic_id' => $appointment->clinic_id,
                'previous_status' => $appointment->getOriginal('status'),
                'new_status' => $this->newStatus,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to update appointment status', [
                'appointment_id' => $this->appointmentId,
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(24);
    }
}
