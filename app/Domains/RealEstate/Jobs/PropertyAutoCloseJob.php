<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Jobs;

use App\Domains\RealEstate\Models\Listing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;

/**
 * Class PropertyAutoCloseJob
 *
 * Part of the RealEstate vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Queued job for async processing.
 * Maintains correlation_id for full traceability.
 * Retries and timeout configured per job.
 *
 * @see \Illuminate\Contracts\Queue\ShouldQueue
 * @package App\Domains\RealEstate\Jobs
 */
final class PropertyAutoCloseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        private readonly Listing $listing,
        private readonly string $correlationId) {
        $this->onQueue('default');
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(6)->toDateTime();
    }

    public function handle(LoggerInterface $logger): void
    {
        try {
            // Если объявление было активным более 90 дней без просмотров, закрыть
            if ($this->listing->status === 'active' && $this->listing->created_at->addDays(90) < now()) {
                $this->listing->update(['status' => 'archived']);

                $logger->info('Property listing auto-closed', [
                    'listing_id' => $this->listing->id,
                    'reason' => 'Inactive for 90 days',
                    'correlation_id' => $this->correlationId,
                ]);
            }
        } catch (\Throwable $e) {
            $logger->error('Property auto-close job failed', [
                'listing_id' => $this->listing->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            throw $e;
        }
    }
}
