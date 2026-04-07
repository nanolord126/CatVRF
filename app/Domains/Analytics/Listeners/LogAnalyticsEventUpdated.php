<?php declare(strict_types=1);

/**
 * LogAnalyticsEventUpdated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/loganalyticseventupdated
 */


namespace App\Domains\Analytics\Listeners;


use Psr\Log\LoggerInterface;
use App\Domains\Analytics\Events\AnalyticsEventUpdated;
/**
 * Class LogAnalyticsEventUpdated
 *
 * Part of the Analytics vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\Analytics\Listeners
 */
final class LogAnalyticsEventUpdated
{
    public function __construct(
        private readonly LoggerInterface $logger) {}

    /**
     * Handle the event.
     */
    public function handle(AnalyticsEventUpdated $event): void
    {
        $this->logger->info('AnalyticsEvent updated', [
            'model_id' => $event->analyticsEvent->id,
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->analyticsEvent->tenant_id ?? null,
        ]);
    }
}
