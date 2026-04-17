<?php declare(strict_types=1);

namespace App\Jobs\Beauty;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;

/**
 * Class ConsumableDeductionJob
 *
 * Queued job for async processing.
 * Maintains correlation_id for full traceability.
 * Retries and timeout configured per job.
 *
 * @see \Illuminate\Contracts\Queue\ShouldQueue
 * @package App\Jobs\Beauty
 */
final class ConsumableDeductionJob implements ShouldQueue
{
    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

        public function __construct(
            private readonly Appointment $appointment,
            private readonly string $correlationId,
            private readonly LogManager $logger,
    ) {
            $this->onQueue('beauty_inventory');
        }

        public function tags(): array
        {
            return ['beauty', 'consumables', 'deduction', 'appointment:' . $this->appointment->id];
        }

        public function handle(ConsumableDeductionService $service): void
        {
            try {
                $this->logger->channel('audit')->info('Job Started: Deduct Consumables', [
                    'appointment_id' => $this->appointment->id,
                    'correlation_id' => $this->correlationId
                ]);

                $service->deductForAppointment($this->appointment, $this->correlationId);

                $this->logger->channel('audit')->info('Job Finished: Deduct Consumables Success', [
                    'appointment_id' => $this->appointment->id,
                    'correlation_id' => $this->correlationId
                ]);

            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Job Failed: Deduct Consumables Error', [
                    'appointment_id' => $this->appointment->id,
                    'correlation_id' => $this->correlationId,
                    'error' => $e->getMessage()
                ]);

                // Release back to queue or handle accordingly
                throw $e;
            }
        }
}

