<?php declare(strict_types=1);

namespace App\Domains\Confectionery\Listeners;



use Psr\Log\LoggerInterface;
use App\Domains\Confectionery\Events\BakeryOrderReady;
use Illuminate\Contracts\Queue\ShouldQueue;
/**
 * Class NotifyBakeryOrderReadyListener
 *
 * Part of the Confectionery vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\Confectionery\Listeners
 */
final class NotifyBakeryOrderReadyListener implements ShouldQueue
{
    public function __construct(
        private readonly \App\Services\AuditService $audit, private readonly LoggerInterface $logger) {}

    /**
     * Handle handle operation.
     *
     * @throws \DomainException
     */
    public function handle(BakeryOrderReady $event): void
    {
        $this->logger->info('NotifyBakeryOrderReadyListener handled', [
            'event' => 'BakeryOrderReady',
            'correlation_id' => $event->correlationId ?? 'N/A',
        ]);
    }

    /**
     * Handle failed operation.
     *
     * @throws \DomainException
     */
    public function failed(BakeryOrderReady $event, \Throwable $exception): void
    {
        $this->logger->error('NotifyBakeryOrderReadyListener failed', [
            'event' => 'BakeryOrderReady',
            'error' => $exception->getMessage(),
            'correlation_id' => $event->correlationId ?? \Illuminate\Support\Str::uuid()->toString(),
        ]);
    }
}