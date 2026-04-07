<?php declare(strict_types=1);

/**
 * LogVeterinaryAppointmentUpdated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/logveterinaryappointmentupdated
 */


namespace App\Domains\Veterinary\Listeners;


use Psr\Log\LoggerInterface;
use App\Domains\Veterinary\Events\VeterinaryAppointmentUpdated;
/**
 * Class LogVeterinaryAppointmentUpdated
 *
 * Part of the Veterinary vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\Veterinary\Listeners
 */
final class LogVeterinaryAppointmentUpdated
{
    public function __construct(
        private readonly LoggerInterface $logger) {}

    /**
     * Handle the event.
     */
    public function handle(VeterinaryAppointmentUpdated $event): void
    {
        $this->logger->info('VeterinaryAppointment updated', [
            'model_id' => $event->veterinaryAppointment->id,
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->veterinaryAppointment->tenant_id ?? null,
        ]);
    }
}
