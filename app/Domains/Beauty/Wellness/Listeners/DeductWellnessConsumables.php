<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Wellness\Listeners;

use App\Domains\Beauty\Wellness\Events\AppointmentCompleted;
use App\Domains\Beauty\Wellness\Models\WellnessService;
use App\Services\InventoryManagementService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * DeductWellnessConsumables - Listens for completed appointments to deduct stock.
 */
final readonly class DeductWellnessConsumables implements ShouldQueue
{
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
