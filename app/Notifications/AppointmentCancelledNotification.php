<?php declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Class AppointmentCancelledNotification
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
final class AppointmentCancelledNotification extends Notification implements ShouldQueue
{
    public function __construct(
        private readonly object $appointment,
        private readonly string $reason,
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
            'reason' => $this->reason,
            'salon_name' => $this->appointment->salon->name ?? '',
            'datetime' => $this->appointment->datetime_start?->format('d.m.Y H:i'),
            'message' => 'Ваша запись отменена',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Запись отменена')
            ->line('Ваша запись была отменена.')
            ->line('Причина: ' . $this->reason)
            ->line('Салон: ' . ($this->appointment->salon->name ?? ''))
            ->line('Дата: ' . ($this->appointment->datetime_start?->format('d.m.Y H:i') ?? ''));
    }
}
