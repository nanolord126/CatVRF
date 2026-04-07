<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Listeners;



use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
use App\Domains\Pharmacy\Events\PharmacyOrderCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
/**
 * Class LogPharmacyOrderCompletedListener
 *
 * Part of the Pharmacy vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\Pharmacy\Listeners
 */
final class LogPharmacyOrderCompletedListener implements ShouldQueue
{
    public function __construct(
        private readonly \App\Services\AuditService $audit, private readonly Request $request, private readonly LoggerInterface $logger) {}

    /**
     * Handle handle operation.
     *
     * @throws \DomainException
     */
    public function handle(PharmacyOrderCompleted $event): void
    {
        $this->logger->info('LogPharmacyOrderCompletedListener handled', [
            'event' => 'PharmacyOrderCompleted',
            'correlation_id' => $event->correlationId ?? 'N/A',
        ]);
    }

    /**
     * Handle failed operation.
     *
     * @throws \DomainException
     */
    public function failed(PharmacyOrderCompleted $event, \Throwable $exception): void
    {
        $this->logger->error('LogPharmacyOrderCompletedListener failed', [
            'event' => 'PharmacyOrderCompleted',
            'error' => $exception->getMessage(),
            'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
        ]);
    }
}