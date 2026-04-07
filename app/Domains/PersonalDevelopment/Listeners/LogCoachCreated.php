<?php declare(strict_types=1);

/**
 * LogCoachCreated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/logcoachcreated
 */


namespace App\Domains\PersonalDevelopment\Listeners;


use Psr\Log\LoggerInterface;
use App\Domains\PersonalDevelopment\Events\CoachCreated;
/**
 * Class LogCoachCreated
 *
 * Part of the PersonalDevelopment vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\PersonalDevelopment\Listeners
 */
final class LogCoachCreated
{
    public function __construct(
        private readonly LoggerInterface $logger) {}

    /**
     * Handle the event.
     */
    public function handle(CoachCreated $event): void
    {
        $this->logger->info('Coach created', [
            'model_id' => $event->coach->id,
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->coach->tenant_id ?? null,
        ]);
    }
}
