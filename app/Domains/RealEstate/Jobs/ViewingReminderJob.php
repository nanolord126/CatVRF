<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Jobs;

use LoggerInterface;

use App\Domains\RealEstate\Models\ViewingAppointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;

/**
 * Class ViewingReminderJob
 *
 * Part of the RealEstate vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Queued job for async processing.
 * Maintains correlation_id for full traceability.
 * Retries and timeout configured per job.
 *
 * @see ShouldQueue
 */
final class ViewingReminderJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly LoggerInterface $loggerInterface,
        private readonly ViewingAppointment $appointment,
        private readonly string $correlationId) {
        $this->onQueue('notifications');
    }

    /**
     * Handle handle operation.
     *
     * @throws \DomainException
     */
    public function handle(LoggerInterface $logger): void
    {
        $logger->$this->logger->info('Sending viewing reminder', [
            'correlation_id' => $this->correlationId,
            'appointment_id' => $this->appointment->id,
        ]);

        // Logic to send reminder notification
        // $this->appointment->client->notify(new ViewingReminderNotification($this->appointment));
    }

    public function failed(Exception $exception): void
    {
        $this->loggerInterface /* TODO: inject via constructor DI */ /* TODO: inject via DI */  // failed() no method injection->error('realestate job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
