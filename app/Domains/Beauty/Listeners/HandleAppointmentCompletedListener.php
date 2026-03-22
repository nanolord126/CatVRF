<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use App\Domains\Beauty\Events\AppointmentCompleted;
use App\Domains\Beauty\Jobs\DeductConsumablesJob;
use App\Domains\Beauty\Jobs\ProcessAppointmentPaymentJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

final class HandleAppointmentCompletedListener implements ShouldQueue
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

        Log::channel('audit')->info('AppointmentCompleted event handled', [
            'appointment_id' => $event->appointment->id,
            'correlation_id' => $event->correlationId,
        ]);
    }
}
