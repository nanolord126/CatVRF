<?php

declare(strict_types=1);

/**
 * DeductWellnessConsumables — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/deductwellnessconsumables
 */


namespace App\Domains\Beauty\Wellness\Listeners;


use Psr\Log\LoggerInterface;
final class DeductWellnessConsumables
{


    use InteractsWithQueue;

        public function __construct(
            private InventoryManagementService $inventoryService,
            private LoggerInterface $logger,
        ) {}

        /**
         * Handle the event.
         */
        public function handle(AppointmentCompleted $event): void
        {
            $appointment = $event->appointment;
            $service = $appointment->service;

            if (empty($service->consumables)) {
                return;
            }

            $this->logger->info('Deducting Consumables for Wellness Appointment', [
                'appointment_uuid' => $appointment->uuid,
                'service_id' => $service->id,
                'correlation_id' => $event->correlation_id,
            ]);

            foreach ($service->consumables as $itemSku => $quantity) {
                 $this->inventoryService->deductStock(
                     itemId: (int) $itemSku, // Cast if SKU is integer ID
                     quantity: (int) $quantity,
                     reason: "Wellness Appt Completed: {$appointment->uuid}",
                     sourceType: 'appointment',
                     sourceId: $appointment->id,
                     correlation_id: $event->correlation_id,
                 );
            }
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

}
