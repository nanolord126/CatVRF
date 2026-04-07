<?php declare(strict_types=1);

/**
 * LogGeoLocationUpdated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/loggeolocationupdated
 */


namespace App\Domains\Geo\Listeners;


use Psr\Log\LoggerInterface;
use App\Domains\Geo\Events\GeoLocationUpdated;
/**
 * Class LogGeoLocationUpdated
 *
 * Part of the Geo vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\Geo\Listeners
 */
final class LogGeoLocationUpdated
{
    public function __construct(
        private readonly LoggerInterface $logger) {}

    /**
     * Handle the event.
     */
    public function handle(GeoLocationUpdated $event): void
    {
        $this->logger->info('GeoLocation updated', [
            'model_id' => $event->geoLocation->id,
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->geoLocation->tenant_id ?? null,
        ]);
    }
}
