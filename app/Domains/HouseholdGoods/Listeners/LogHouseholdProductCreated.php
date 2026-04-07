<?php declare(strict_types=1);

/**
 * LogHouseholdProductCreated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/loghouseholdproductcreated
 */


namespace App\Domains\HouseholdGoods\Listeners;


use Psr\Log\LoggerInterface;
use App\Domains\HouseholdGoods\Events\HouseholdProductCreated;
/**
 * Class LogHouseholdProductCreated
 *
 * Part of the HouseholdGoods vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\HouseholdGoods\Listeners
 */
final class LogHouseholdProductCreated
{
    public function __construct(
        private readonly LoggerInterface $logger) {}

    /**
     * Handle the event.
     */
    public function handle(HouseholdProductCreated $event): void
    {
        $this->logger->info('HouseholdProduct created', [
            'model_id' => $event->householdProduct->id,
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->householdProduct->tenant_id ?? null,
        ]);
    }
}
