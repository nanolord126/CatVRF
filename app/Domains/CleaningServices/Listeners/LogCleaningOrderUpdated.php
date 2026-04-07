<?php declare(strict_types=1);

namespace App\Domains\CleaningServices\Listeners;



use App\Services\FraudControlService;
use Psr\Log\LoggerInterface;
use App\Domains\CleaningServices\Events\CleaningOrderUpdated;
/**
 * Class LogCleaningOrderUpdated
 *
 * Part of the CleaningServices vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\CleaningServices\Listeners
 */
final class LogCleaningOrderUpdated
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly LoggerInterface $logger) {}

    /**
     * Handle the event.
     */
    public function handle(CleaningOrderUpdated $event): void
    {
        $this->logger->info('CleaningOrder updated', [
            'model_id' => $event->cleaningOrder->id,
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->cleaningOrder->tenant_id ?? null,
        ]);
    }
}
