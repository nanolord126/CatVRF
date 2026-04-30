<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Jobs;

use LoggerInterface;

use Carbon\CarbonImmutable;

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
 * @see ShouldQueue
 */
final class PropertyAutoCloseJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public array $backoff = [60, 300, 900];

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(private readonly LoggerInterface $loggerInterface,
        private readonly Listing $listing,
        private readonly string $correlationId) {
        $this->onQueue('default');
    }

    public function retryUntil(): \DateTime
    {
        return CarbonImmutable::now()->addHours(6)->toDateTime();
    }

    public function handle(LoggerInterface $logger): void
    {
        try {
            // Если объявление было активным более 90 дней без просмотров, закрыть
            if ($this->listing->status === 'active' && $this->listing->created_at->addDays(90) < CarbonImmutable::now()) {
                $this->listing->update(['status' => 'archived']);

                $logger->$this->logger->info('Property listing auto-closed', [
                    'listing_id' => $this->listing->id,
                    'reason' => 'Inactive for 90 days',
                    'correlation_id' => $this->correlationId,
                ]);
            }
        } catch (Exception $e) {
            $logger->error('Property auto-close job failed', [
                'listing_id' => $this->listing->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            throw $e;
        }
    }

    public function failed(Exception $exception): void
    {
        $this->loggerInterface /* TODO: inject via constructor DI */ /* TODO: inject via DI */  // failed() no method injection->error('realestate job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
