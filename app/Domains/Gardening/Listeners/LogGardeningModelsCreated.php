<?php declare(strict_types=1);

/**
 * LogGardeningModelsCreated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/loggardeningmodelscreated
 */


namespace App\Domains\Gardening\Listeners;


use Psr\Log\LoggerInterface;
use App\Domains\Gardening\Events\GardeningModelsCreated;
/**
 * Class LogGardeningModelsCreated
 *
 * Part of the Gardening vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\Gardening\Listeners
 */
final class LogGardeningModelsCreated
{
    public function __construct(
        private readonly LoggerInterface $logger) {}

    /**
     * Handle the event.
     */
    public function handle(GardeningModelsCreated $event): void
    {
        $this->logger->info('GardeningModels created', [
            'model_id' => $event->gardeningModels->id,
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->gardeningModels->tenant_id ?? null,
        ]);
    }
}
