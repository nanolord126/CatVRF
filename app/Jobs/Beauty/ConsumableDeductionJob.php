<?php declare(strict_types=1);

namespace App\Jobs\Beauty;

use App\Services\InventoryManagementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class ConsumableDeductionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $appointmentId,
        private readonly int $tenantId,
    ) {
        $this->onQueue('inventory');
    }

    public function tags(): array
    {
        return ['beauty', 'consumable', 'inventory', $this->tenantId];
    }

    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(15);
    }

    public function handle(InventoryManagementService $inventoryService): void
    {
        $correlationId = Str::uuid()->toString();

        try {
            DB::transaction(function () use ($inventoryService, $correlationId) {
                $appointment = $inventoryService->getAppointmentWithConsumables($this->appointmentId);

                if (! $appointment) {
                    Log::channel('audit')->warning('Appointment not found for consumable deduction', [
                        'correlation_id' => $correlationId,
                        'appointment_id' => $this->appointmentId,
                        'tenant_id' => $this->tenantId,
                    ]);

                    return;
                }

                foreach ($appointment->service->consumables as $consumable) {
                    $inventoryService->deductStock(
                        itemId: $consumable->id,
                        quantity: $consumable->quantity,
                        reason: "Appointment #{$this->appointmentId} completed",
                        sourceType: 'appointment',
                        sourceId: $this->appointmentId
                    );

                    Log::channel('audit')->info('Consumable deducted', [
                        'correlation_id' => $correlationId,
                        'consumable_id' => $consumable->id,
                        'quantity' => $consumable->quantity,
                        'appointment_id' => $this->appointmentId,
                        'tenant_id' => $this->tenantId,
                    ]);
                }
            });
        } catch (\Exception $e) {
            Log::channel('audit')->error('Consumable deduction job failed', [
                'correlation_id' => $correlationId,
                'appointment_id' => $this->appointmentId,
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
