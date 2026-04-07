<?php declare(strict_types=1);

namespace App\Domains\MeatShops\Listeners;



use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
use App\Domains\MeatShops\Events\MeatShopCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
/**
 * Class LogMeatShopCreatedListener
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
final class LogMeatShopCreatedListener implements ShouldQueue
{
    public function __construct(
        private readonly \App\Services\AuditService $audit, private readonly Request $request, private readonly LoggerInterface $logger) {}

    /**
     * Handle handle operation.
     *
     * @throws \DomainException
     */
    public function handle(MeatShopCreated $event): void
    {
        $this->logger->info('LogMeatShopCreatedListener handled', [
            'event' => 'MeatShopCreated',
            'correlation_id' => $event->correlationId ?? 'N/A',
        ]);
    }

    /**
     * Handle failed operation.
     *
     * @throws \DomainException
     */
    public function failed(MeatShopCreated $event, \Throwable $exception): void
    {
        $this->logger->error('LogMeatShopCreatedListener failed', [
            'event' => 'MeatShopCreated',
            'error' => $exception->getMessage(),
            'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
        ]);
    }
}