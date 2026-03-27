<?php

declare(strict_types=1);


namespace App\Domains\Beauty\Jobs;

use App\Domains\Beauty\Models\Appointment;
use App\Services\InventoryManagementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final /**
 * DeductConsumablesJob
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class DeductConsumablesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $appointmentId,
        private readonly string $correlationId,
    ) {}

    public function handle(InventoryManagementService $inventory): void
    {
        $appointment = Appointment::findOrFail($this->appointmentId);

        DB::transaction(function () use ($appointment, $inventory): void {
            $consumables = $appointment->service->consumables_json ?? [];

            foreach ($consumables as $consumable) {
                $inventory->deductStock(
                    $consumable['id'],
                    $consumable['quantity'],
                    'appointment_completed',
                    'appointment',
                    $appointment->id
                );
            }

            Log::channel('audit')->info('Consumables deducted', [
                'appointment_id' => $appointment->id,
                'correlation_id' => $this->correlationId,
            ]);
        });
    }
}
