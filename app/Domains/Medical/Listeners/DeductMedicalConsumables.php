<?php

declare(strict_types=1);

namespace App\Domains\Medical\Listeners;

use App\Domains\Medical\Events\MedicalAppointmentCompleted;
use App\Services\InventoryManagementService;
use Illuminate\Support\Facades\Log;

/**
 * КАНОН 2026 — DEDUCT MEDICAL CONSUMABLES LISTENER
 * Автоматическое списание расходников после завершения приема
 */
final readonly class DeductMedicalConsumables
{
    public function __construct(
        private InventoryManagementService $inventory
    ) {}

    public function handle(MedicalAppointmentCompleted $event): void
    {
        $appointment = $event->appointment;
        $service = $appointment->service;

        if (empty($service->consumables_json)) {
            return;
        }

        try {
            foreach ($service->consumables_json as $item) {
                $this->inventory->deductStock(
                    itemId: $item['inventory_item_id'],
                    quantity: $item['quantity'],
                    reason: "Списание после приема #{$appointment->appointment_number}",
                    sourceType: 'medical_appointment',
                    sourceId: $appointment->id
                );
            }

            Log::channel('audit')->info('Medical consumables deducted', [
                'appointment_id' => $appointment->id,
                'correlation_id' => $event->correlation_id,
                'items_count' => count($service->consumables_json),
            ]);

        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to deduct medical consumables', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
