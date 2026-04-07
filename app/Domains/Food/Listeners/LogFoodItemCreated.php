<?php declare(strict_types=1);

/**
 * LogFoodItemCreated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/logfooditemcreated
 */


namespace App\Domains\Food\Listeners;


use Psr\Log\LoggerInterface;
use App\Domains\Food\Events\FoodItemCreated;
/**
 * Class LogFoodItemCreated
 *
 * Part of the Food vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\Food\Listeners
 */
final class LogFoodItemCreated
{
    public function __construct(
        private readonly LoggerInterface $logger) {}

    /**
     * Handle the event.
     */
    public function handle(FoodItemCreated $event): void
    {
        $this->logger->info('FoodItem created', [
            'model_id' => $event->foodItem->id,
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->foodItem->tenant_id ?? null,
        ]);
    }
}
