<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Domains\Beauty\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class AppointmentReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Appointment $appointment,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'appointment_id' => $this->appointment->id,
            'master_name' => $this->appointment->master->full_name ?? '',
            'service_name' => $this->appointment->service->name ?? '',
            'datetime' => $this->appointment->datetime_start?->format('d.m.Y H:i'),
            'salon_address' => $this->appointment->salon->address ?? '',
            'message' => 'Напоминаем о записи завтра',
        ];
    }
}
