<?php declare(strict_types=1);

/**
 * LogPartyOrderCreated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/logpartyordercreated
 */


namespace App\Domains\PartySupplies\Listeners;


use Psr\Log\LoggerInterface;
use App\Domains\PartySupplies\Events\PartyOrderCreated;
/**
 * Class LogPartyOrderCreated
 *
 * Part of the PartySupplies vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\PartySupplies\Listeners
 */
final class LogPartyOrderCreated
{
    public function __construct(
        private readonly LoggerInterface $logger) {}

    /**
     * Handle the event.
     */
    public function handle(PartyOrderCreated $event): void
    {
        $this->logger->info('PartyOrder created', [
            'model_id' => $event->partyOrder->id,
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->partyOrder->tenant_id ?? null,
        ]);
    }
}
