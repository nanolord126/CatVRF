<?php declare(strict_types=1);

/**
 * LogPromoAuditLogUpdated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/logpromoauditlogupdated
 */


namespace App\Domains\PromoCampaigns\Listeners;


use Psr\Log\LoggerInterface;
use App\Domains\PromoCampaigns\Events\PromoAuditLogUpdated;
/**
 * Class LogPromoAuditLogUpdated
 *
 * Part of the PromoCampaigns vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\PromoCampaigns\Listeners
 */
final class LogPromoAuditLogUpdated
{
    public function __construct(
        private readonly LoggerInterface $logger) {}

    /**
     * Handle the event.
     */
    public function handle(PromoAuditLogUpdated $event): void
    {
        $this->logger->info('PromoAuditLog updated', [
            'model_id' => $event->promoAuditLog->id,
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->promoAuditLog->tenant_id ?? null,
        ]);
    }
}
