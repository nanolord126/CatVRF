<?php declare(strict_types=1);

/**
 * LogCraftItemUpdated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/logcraftitemupdated
 */


namespace App\Domains\HobbyAndCraft\Listeners;


use Psr\Log\LoggerInterface;
use App\Domains\HobbyAndCraft\Events\CraftItemUpdated;
/**
 * Class LogCraftItemUpdated
 *
 * Part of the HobbyAndCraft vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\HobbyAndCraft\Listeners
 */
final class LogCraftItemUpdated
{
    public function __construct(
        private readonly LoggerInterface $logger) {}

    /**
     * Handle the event.
     */
    public function handle(CraftItemUpdated $event): void
    {
        $this->logger->info('CraftItem updated', [
            'model_id' => $event->craftItem->id,
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->craftItem->tenant_id ?? null,
        ]);
    }
}
