<?php declare(strict_types=1);

/**
 * LogRecommendationCreated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/logrecommendationcreated
 */


namespace App\Domains\Recommendation\Listeners;


use Psr\Log\LoggerInterface;
use App\Domains\Recommendation\Events\RecommendationCreated;
/**
 * Class LogRecommendationCreated
 *
 * Part of the Recommendation vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\Recommendation\Listeners
 */
final class LogRecommendationCreated
{
    public function __construct(
        private readonly LoggerInterface $logger) {}

    /**
     * Handle the event.
     */
    public function handle(RecommendationCreated $event): void
    {
        $this->logger->info('Recommendation created', [
            'model_id' => $event->recommendation->id,
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->recommendation->tenant_id ?? null,
        ]);
    }
}
