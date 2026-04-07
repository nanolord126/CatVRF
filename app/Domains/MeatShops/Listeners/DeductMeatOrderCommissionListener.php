<?php declare(strict_types=1);

namespace App\Domains\MeatShops\Listeners;



use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
use App\Domains\MeatShops\Events\MeatOrderCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
/**
 * Class DeductMeatOrderCommissionListener
 *
 * Part of the MeatShops vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\MeatShops\Listeners
 */
final class DeductMeatOrderCommissionListener implements ShouldQueue
{
    public function __construct(
        private readonly \App\Services\AuditService $audit, private readonly Request $request, private readonly LoggerInterface $logger) {}

    /**
     * Handle handle operation.
     *
     * @throws \DomainException
     */
    public function handle(MeatOrderCreated $event): void
    {
        $this->logger->info('DeductMeatOrderCommissionListener handled', [
            'event' => 'MeatOrderCreated',
            'correlation_id' => $event->correlationId ?? 'N/A',
        ]);
    }

    /**
     * Handle failed operation.
     *
     * @throws \DomainException
     */
    public function failed(MeatOrderCreated $event, \Throwable $exception): void
    {
        $this->logger->error('DeductMeatOrderCommissionListener failed', [
            'event' => 'MeatOrderCreated',
            'error' => $exception->getMessage(),
            'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
        ]);
    }
}