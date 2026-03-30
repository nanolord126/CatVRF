<?php declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HandleAppointmentCompletedListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function handle(AppointmentCompleted $event): void
        {
            DeductConsumablesJob::dispatch(
                $event->appointment->id,
                $event->correlationId
            );

            ProcessAppointmentPaymentJob::dispatch(
                $event->appointment->id,
                $event->correlationId
            );

            Log::channel('audit')->info('AppointmentCompleted event handled', [
                'appointment_id' => $event->appointment->id,
                'correlation_id' => $event->correlationId,
            ]);
        }
}
