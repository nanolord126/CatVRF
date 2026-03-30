<?php declare(strict_types=1);

namespace App\Domains\Beauty\Wellness\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DeductWellnessConsumables extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use InteractsWithQueue;

        public function __construct(
            private readonly InventoryManagementService $inventoryService,
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

            Log::channel('inventory')->info('Deducting Consumables for Wellness Appointment', [
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
}
