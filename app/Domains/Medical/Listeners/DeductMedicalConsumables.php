<?php declare(strict_types=1);

namespace App\Domains\Medical\Listeners;



use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
final class DeductMedicalConsumables
{

    public function __construct(
            private InventoryManagementService $inventory, private readonly Request $request, private readonly LoggerInterface $logger
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

                $this->logger->info('Medical consumables deducted', [
                    'appointment_id' => $appointment->id,
                    'correlation_id' => $event->correlation_id,
                    'items_count' => count($service->consumables_json),
                ]);

            } catch (\Throwable $e) {
                $this->logger->error('Failed to deduct medical consumables', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
            }
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
