<?php declare(strict_types=1);

namespace App\Domains\Analytics\Domain\Listeners;



use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
use App\Domains\Analytics\Domain\Events\AnalyticsEventTracked;
use Illuminate\Contracts\Queue\ShouldQueue;
/**
 * Class ProcessAnalyticsEventListener
 *
 * Part of the Analytics vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\Analytics\Domain\Listeners
 */
final class ProcessAnalyticsEventListener implements ShouldQueue
{
    public function __construct(
        private readonly \App\Services\AuditService $audit, private readonly Request $request, private readonly LoggerInterface $logger) {}

    /**
     * Handle handle operation.
     *
     * @throws \DomainException
     */
    public function handle(AnalyticsEventTracked $event): void
    {
        $this->logger->info('ProcessAnalyticsEventListener handled', [
            'event' => 'AnalyticsEventTracked',
            'correlation_id' => $event->correlationId ?? 'N/A',
        ]);
    }

    /**
     * Handle failed operation.
     *
     * @throws \DomainException
     */
    public function failed(AnalyticsEventTracked $event, \Throwable $exception): void
    {
        $this->logger->error('ProcessAnalyticsEventListener failed', [
            'event' => 'AnalyticsEventTracked',
            'error' => $exception->getMessage(),
            'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
        ]);
    }
}