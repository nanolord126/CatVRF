<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Listeners;



use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
use App\Domains\Pharmacy\Events\PharmacyCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
/**
 * Class LogPharmacyCreatedListener
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
final class LogPharmacyCreatedListener implements ShouldQueue
{
    public function __construct(
        private readonly \App\Services\AuditService $audit, private readonly Request $request, private readonly LoggerInterface $logger) {}

    /**
     * Handle handle operation.
     *
     * @throws \DomainException
     */
    public function handle(PharmacyCreated $event): void
    {
        $this->logger->info('LogPharmacyCreatedListener handled', [
            'event' => 'PharmacyCreated',
            'correlation_id' => $event->correlationId ?? 'N/A',
        ]);
    }

    /**
     * Handle failed operation.
     *
     * @throws \DomainException
     */
    public function failed(PharmacyCreated $event, \Throwable $exception): void
    {
        $this->logger->error('LogPharmacyCreatedListener failed', [
            'event' => 'PharmacyCreated',
            'error' => $exception->getMessage(),
            'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
        ]);
    }
}