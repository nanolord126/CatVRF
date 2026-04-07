<?php declare(strict_types=1);

namespace App\Domains\OfficeCatering\Listeners;



use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
use App\Domains\OfficeCatering\Events\CorporateOrderCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
/**
 * Class DeductCateringOrderCommissionListener
 *
 * Part of the OfficeCatering vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\OfficeCatering\Listeners
 */
final class DeductCateringOrderCommissionListener implements ShouldQueue
{
    public function __construct(
        private readonly \App\Services\AuditService $audit, private readonly Request $request, private readonly LoggerInterface $logger) {}

    /**
     * Handle handle operation.
     *
     * @throws \DomainException
     */
    public function handle(CorporateOrderCreated $event): void
    {
        $this->logger->info('DeductCateringOrderCommissionListener handled', [
            'event' => 'CorporateOrderCreated',
            'correlation_id' => $event->correlationId ?? 'N/A',
        ]);
    }

    /**
     * Handle failed operation.
     *
     * @throws \DomainException
     */
    public function failed(CorporateOrderCreated $event, \Throwable $exception): void
    {
        $this->logger->error('DeductCateringOrderCommissionListener failed', [
            'event' => 'CorporateOrderCreated',
            'error' => $exception->getMessage(),
            'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
        ]);
    }
}