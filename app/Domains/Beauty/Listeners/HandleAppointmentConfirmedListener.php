<?php

declare(strict_types=1);


namespace App\Domains\Beauty\Listeners;

use App\Domains\Beauty\Events\AppointmentConfirmed;
use App\Domains\Beauty\Jobs\SendAppointmentRemindersJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

final /**
 * HandleAppointmentConfirmedListener
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class HandleAppointmentConfirmedListener implements ShouldQueue
{
    public function handle(AppointmentConfirmed $event): void
    {
        $appointment = $event->appointment;

        // Schedule reminder job (24h before appointment)
        $reminderTime = $appointment->datetime_start?->subHours(24);
        
        if ($reminderTime && $reminderTime->isFuture()) {
            SendAppointmentRemindersJob::dispatch($event->correlationId)
                ->delay($reminderTime);
        }

        // Notify client immediately
        if ($appointment->client) {
            $this->notification->send(
                $appointment->client,
                new \App\Notifications\AppointmentConfirmedNotification($appointment)
            );
        }

        Log::channel('audit')->info('AppointmentConfirmed event handled', [
            'appointment_id' => $appointment->id,
            'reminder_scheduled_at' => $reminderTime?->toDateTimeString(),
            'correlation_id' => $event->correlationId,
        ]);
    }
}
