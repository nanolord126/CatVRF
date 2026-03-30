<?php declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HandleAppointmentConfirmedListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
