<?php declare(strict_types=1);

namespace App\Domains\CarRental\Jobs;



use Psr\Log\LoggerInterface;
use App\Domains\CarRental\Models\RentalBooking;
use App\Services\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
/**
 * Class ProcessRentalBookingJob
 *
 * Part of the CarRental vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Queued job for async processing.
 * Maintains correlation_id for full traceability.
 * Retries and timeout configured per job.
 *
 * @see \Illuminate\Contracts\Queue\ShouldQueue
 * @package App\Domains\CarRental\Jobs
 */
final class ProcessRentalBookingJob implements ShouldQueue
{

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly int $modelId,
        private readonly string $correlationId, private readonly LoggerInterface $logger) {
        $this->onQueue('car_rental');
    }

    public function handle(AuditService $audit): void
    {
        $model = RentalBooking::findOrFail($this->modelId);

        $this->logger->info('ProcessRentalBookingJob processed', [
            'model_id' => $model->id,
            'correlation_id' => $this->correlationId,
            'tenant_id' => $model->tenant_id ?? null,
        ]);

        $audit->log(
            action: 'car_rental_job_processed',
            subjectType: RentalBooking::class,
            subjectId: $model->id,
            correlationId: $this->correlationId,
        );
    }

    public function failed(\Throwable $e): void
    {
        $this->logger->error('ProcessRentalBookingJob failed', [
            'model_id' => $this->modelId,
            'error' => $e->getMessage(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}