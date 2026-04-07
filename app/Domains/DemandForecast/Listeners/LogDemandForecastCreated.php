<?php declare(strict_types=1);

/**
 * LogDemandForecastCreated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/logdemandforecastcreated
 */


namespace App\Domains\DemandForecast\Listeners;


use Psr\Log\LoggerInterface;
use App\Domains\DemandForecast\Events\DemandForecastCreated;
/**
 * Class LogDemandForecastCreated
 *
 * Part of the DemandForecast vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\DemandForecast\Listeners
 */
final class LogDemandForecastCreated
{
    public function __construct(
        private readonly LoggerInterface $logger) {}

    /**
     * Handle the event.
     */
    public function handle(DemandForecastCreated $event): void
    {
        $this->logger->info('DemandForecast created', [
            'model_id' => $event->demandForecast->id,
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->demandForecast->tenant_id ?? null,
        ]);
    }
}
