<?php declare(strict_types=1);

/**
 * LogEventUpdated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/logeventupdated
 */


namespace App\Domains\EventPlanning\Listeners;


use Psr\Log\LoggerInterface;
use App\Domains\EventPlanning\Events\EventUpdated;
/**
 * Class LogEventUpdated
 *
 * Part of the EventPlanning vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\EventPlanning\Listeners
 */
final class LogEventUpdated
{
    public function __construct(
        private readonly LoggerInterface $logger) {}

    /**
     * Handle the event.
     */
    public function handle(EventUpdated $event): void
    {
        $this->logger->info('Event updated', [
            'model_id' => $event->event->id,
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->event->tenant_id ?? null,
        ]);
    }
}
