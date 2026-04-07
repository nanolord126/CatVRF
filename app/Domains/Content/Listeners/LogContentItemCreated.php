<?php declare(strict_types=1);

/**
 * LogContentItemCreated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/logcontentitemcreated
 */


namespace App\Domains\Content\Listeners;


use Psr\Log\LoggerInterface;
use App\Domains\Content\Events\ContentItemCreated;
/**
 * Class LogContentItemCreated
 *
 * Part of the Content vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\Content\Listeners
 */
final class LogContentItemCreated
{
    public function __construct(
        private readonly LoggerInterface $logger) {}

    /**
     * Handle the event.
     */
    public function handle(ContentItemCreated $event): void
    {
        $this->logger->info('ContentItem created', [
            'model_id' => $event->contentItem->id,
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->contentItem->tenant_id ?? null,
        ]);
    }
}
