<?php declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Class AppointmentConfirmedNotification
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Notifications
 */
final class AppointmentConfirmedNotification extends Notification implements ShouldQueue
{
    public function __construct(
        private readonly object $appointment,
    )
    {
        // Implementation required by canon
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'appointment_id' => $this->appointment->id ?? null,
            'master_name' => $this->appointment->master->full_name ?? '',
            'service_name' => $this->appointment->service->name ?? '',
            'datetime' => $this->appointment->datetime_start?->format('d.m.Y H:i'),
            'message' => 'Ваша запись подтверждена',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Запись подтверждена')
            ->line('Ваша запись подтверждена!')
            ->line('Мастер: ' . ($this->appointment->master->full_name ?? ''))
            ->line('Услуга: ' . ($this->appointment->service->name ?? ''))
            ->line('Дата и время: ' . ($this->appointment->datetime_start?->format('d.m.Y H:i') ?? ''))
            ->line('Адрес: ' . ($this->appointment->salon->address ?? ''));
    }
}
