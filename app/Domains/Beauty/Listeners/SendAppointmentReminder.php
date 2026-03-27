<?php

declare(strict_types=1);


namespace App\Domains\Beauty\Listeners;

use App\Domains\Beauty\Events\AppointmentScheduled;
use Illuminate\Support\Facades\Log;

final /**
 * SendAppointmentReminder
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class SendAppointmentReminder
{
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
