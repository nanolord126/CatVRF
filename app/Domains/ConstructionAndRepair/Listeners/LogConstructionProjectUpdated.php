<?php declare(strict_types=1);

/**
 * LogConstructionProjectUpdated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/logconstructionprojectupdated
 */


namespace App\Domains\ConstructionAndRepair\Listeners;


use Psr\Log\LoggerInterface;
use App\Domains\ConstructionAndRepair\Events\ConstructionProjectUpdated;
/**
 * Class LogConstructionProjectUpdated
 *
 * Part of the ConstructionAndRepair vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\ConstructionAndRepair\Listeners
 */
final class LogConstructionProjectUpdated
{
    public function __construct(
        private readonly LoggerInterface $logger) {}

    /**
     * Handle the event.
     */
    public function handle(ConstructionProjectUpdated $event): void
    {
        $this->logger->info('ConstructionProject updated', [
            'model_id' => $event->constructionProject->id,
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->constructionProject->tenant_id ?? null,
        ]);
    }
}
