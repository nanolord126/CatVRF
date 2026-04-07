<?php declare(strict_types=1);

/**
 * LogRentalBookingCreated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/logrentalbookingcreated
 */


namespace App\Domains\CarRental\Listeners;


use Psr\Log\LoggerInterface;
use App\Domains\CarRental\Events\RentalBookingCreated;
/**
 * Class LogRentalBookingCreated
 *
 * Part of the CarRental vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\CarRental\Listeners
 */
final class LogRentalBookingCreated
{
    public function __construct(
        private readonly LoggerInterface $logger) {}

    /**
     * Handle the event.
     */
    public function handle(RentalBookingCreated $event): void
    {
        $this->logger->info('RentalBooking created', [
            'model_id' => $event->rentalBooking->id,
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->rentalBooking->tenant_id ?? null,
        ]);
    }
}
