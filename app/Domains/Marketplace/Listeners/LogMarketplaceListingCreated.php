<?php declare(strict_types=1);

/**
 * LogMarketplaceListingCreated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/logmarketplacelistingcreated
 */


namespace App\Domains\Marketplace\Listeners;


use Psr\Log\LoggerInterface;
use App\Domains\Marketplace\Events\MarketplaceListingCreated;
/**
 * Class LogMarketplaceListingCreated
 *
 * Part of the Marketplace vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\Marketplace\Listeners
 */
final class LogMarketplaceListingCreated
{
    public function __construct(
        private readonly LoggerInterface $logger) {}

    /**
     * Handle the event.
     */
    public function handle(MarketplaceListingCreated $event): void
    {
        $this->logger->info('MarketplaceListing created', [
            'model_id' => $event->marketplaceListing->id,
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->marketplaceListing->tenant_id ?? null,
        ]);
    }
}
