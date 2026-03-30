<?php declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DeductConsumablesJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
