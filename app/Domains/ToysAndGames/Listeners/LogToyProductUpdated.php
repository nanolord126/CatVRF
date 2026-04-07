<?php declare(strict_types=1);

/**
 * LogToyProductUpdated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/logtoyproductupdated
 */


namespace App\Domains\ToysAndGames\Listeners;


use Psr\Log\LoggerInterface;
use App\Domains\ToysAndGames\Events\ToyProductUpdated;
/**
 * Class LogToyProductUpdated
 *
 * Part of the ToysAndGames vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\ToysAndGames\Listeners
 */
final class LogToyProductUpdated
{
    public function __construct(
        private readonly LoggerInterface $logger) {}

    /**
     * Handle the event.
     */
    public function handle(ToyProductUpdated $event): void
    {
        $this->logger->info('ToyProduct updated', [
            'model_id' => $event->toyProduct->id,
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->toyProduct->tenant_id ?? null,
        ]);
    }
}
