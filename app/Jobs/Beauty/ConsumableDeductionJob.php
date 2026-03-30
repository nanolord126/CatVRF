<?php declare(strict_types=1);

namespace App\Jobs\Beauty;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ConsumableDeductionJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public function __construct(
            private readonly Appointment $appointment,
            private readonly string $correlationId
        ) {
            $this->onQueue('beauty_inventory');
        }

        public function tags(): array
        {
            return ['beauty', 'consumables', 'deduction', 'appointment:' . $this->appointment->id];
        }

        public function handle(ConsumableDeductionService $service): void
        {
            try {
                Log::channel('audit')->info('Job Started: Deduct Consumables', [
                    'appointment_id' => $this->appointment->id,
                    'correlation_id' => $this->correlationId
                ]);

                $service->deductForAppointment($this->appointment, $this->correlationId);

                Log::channel('audit')->info('Job Finished: Deduct Consumables Success', [
                    'appointment_id' => $this->appointment->id,
                    'correlation_id' => $this->correlationId
                ]);

            } catch (\Throwable $e) {
                Log::channel('audit')->error('Job Failed: Deduct Consumables Error', [
                    'appointment_id' => $this->appointment->id,
                    'correlation_id' => $this->correlationId,
                    'error' => $e->getMessage()
                ]);

                // Release back to queue or handle accordingly
                throw $e;
            }
        }
}
