<?php declare(strict_types=1);

/**
 * LogLogisticsRouteCreated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/loglogisticsroutecreated
 */


namespace App\Domains\GeoLogistics\Listeners;


use Psr\Log\LoggerInterface;
use App\Domains\GeoLogistics\Events\LogisticsRouteCreated;
/**
 * Class LogLogisticsRouteCreated
 *
 * Part of the GeoLogistics vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\GeoLogistics\Listeners
 */
final class LogLogisticsRouteCreated
{
    public function __construct(
        private readonly LoggerInterface $logger) {}

    /**
     * Handle the event.
     */
    public function handle(LogisticsRouteCreated $event): void
    {
        $this->logger->info('LogisticsRoute created', [
            'model_id' => $event->logisticsRoute->id,
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->logisticsRoute->tenant_id ?? null,
        ]);
    }
}
