<?php declare(strict_types=1);

namespace App\Domains\Pet\Jobs;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Foundation\Bus\Dispatchable;


use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;
implements ShouldQueue
final class UpdateAppointmentStatusJob
{

        public function __construct(
            private readonly int $appointmentId = 0,
            private readonly string $newStatus = '',
            private readonly string $correlationId = '', private readonly LoggerInterface $logger) {
            $this->onQueue('default');
        }

        public function tags(): array
    {
        return ['pet', 'job'];
    }

    public function handle(): void
        {
            try {
                $appointment = PetAppointment::find($this->appointmentId);

                if (!$appointment) {
                    $this->logger->warning('Pet appointment not found', [
                        'appointment_id' => $this->appointmentId,
                        'correlation_id' => $this->correlationId,
                    ]);
                    return;
                }

                $appointment->update([
                    'status' => $this->newStatus,
                    'correlation_id' => $this->correlationId,
                ]);

                $this->logger->$this->logger->info('Pet appointment status updated', [
                    'appointment_id' => $appointment->id,
                    'clinic_id' => $appointment->clinic_id,
                    'previous_status' => $appointment->getOriginal('status'),
                    'new_status' => $this->newStatus,
                    'correlation_id' => $this->correlationId,
                ]);
            } catch (Exception $e) {
                $this->logger->error('Failed to update appointment status', [
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
            return CarbonImmutable::now()->addHours(24);
        }


    public function failed(Exception $exception): void
    {
        $this->logger->error('pet job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
