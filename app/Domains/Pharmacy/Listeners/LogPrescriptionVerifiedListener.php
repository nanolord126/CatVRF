<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Listeners;



use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
use App\Domains\Pharmacy\Events\PrescriptionVerified;
use Illuminate\Contracts\Queue\ShouldQueue;
/**
 * Class LogPrescriptionVerifiedListener
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
final class LogPrescriptionVerifiedListener implements ShouldQueue
{
    public function __construct(
        private readonly \App\Services\AuditService $audit, private readonly Request $request, private readonly LoggerInterface $logger) {}

    /**
     * Handle handle operation.
     *
     * @throws \DomainException
     */
    public function handle(PrescriptionVerified $event): void
    {
        $this->logger->info('LogPrescriptionVerifiedListener handled', [
            'event' => 'PrescriptionVerified',
            'correlation_id' => $event->correlationId ?? 'N/A',
        ]);
    }

    /**
     * Handle failed operation.
     *
     * @throws \DomainException
     */
    public function failed(PrescriptionVerified $event, \Throwable $exception): void
    {
        $this->logger->error('LogPrescriptionVerifiedListener failed', [
            'event' => 'PrescriptionVerified',
            'error' => $exception->getMessage(),
            'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
        ]);
    }
}