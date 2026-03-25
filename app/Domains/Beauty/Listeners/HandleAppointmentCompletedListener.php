declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use App\Domains\Beauty\Events\AppointmentCompleted;
use App\Domains\Beauty\Jobs\DeductConsumablesJob;
use App\Domains\Beauty\Jobs\ProcessAppointmentPaymentJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

final /**
 * HandleAppointmentCompletedListener
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class HandleAppointmentCompletedListener implements ShouldQueue
{
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

        $this->log->channel('audit')->info('AppointmentCompleted event handled', [
            'appointment_id' => $event->appointment->id,
            'correlation_id' => $event->correlationId,
        ]);
    }
}
