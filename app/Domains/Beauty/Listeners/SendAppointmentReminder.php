<?php declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SendAppointmentReminder extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function handle(AppointmentScheduled $event): void
        {
            try {
                Log::channel('audit')->info('Appointment reminder scheduled', [
                    'appointment_id' => $event->appointmentId,
                    'master_id' => $event->masterId,
                    'client_id' => $event->clientId,
                    'scheduled_at' => $event->scheduledAt,
                    'correlation_id' => $event->correlationId,
                    'action' => 'appointment_reminder_scheduled',
                ]);
                // Notification::dispatch(new AppointmentReminderNotification($event), delay: 23 hours);
            } catch (\Exception $e) {
                Log::channel('audit')->error('Failed to schedule appointment reminder', [
                    'correlation_id' => $event->correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
}
