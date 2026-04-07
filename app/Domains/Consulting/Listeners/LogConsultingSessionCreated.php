<?php declare(strict_types=1);

/**
 * LogConsultingSessionCreated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/logconsultingsessioncreated
 */


namespace App\Domains\Consulting\Listeners;


use Psr\Log\LoggerInterface;
use App\Domains\Consulting\Events\ConsultingSessionCreated;
/**
 * Class LogConsultingSessionCreated
 *
 * Part of the Consulting vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\Consulting\Listeners
 */
final class LogConsultingSessionCreated
{
    public function __construct(
        private readonly LoggerInterface $logger) {}

    /**
     * Handle the event.
     */
    public function handle(ConsultingSessionCreated $event): void
    {
        $this->logger->info('ConsultingSession created', [
            'model_id' => $event->consultingSession->id,
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->consultingSession->tenant_id ?? null,
        ]);
    }
}
