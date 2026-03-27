<?php

declare(strict_types=1);


namespace App\Notifications;

use App\Domains\Beauty\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final /**
 * AppointmentCancelledNotification
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class AppointmentCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Appointment $appointment,
        private readonly string $reason,
    ) {
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'appointment_id' => $this->appointment->id,
            'reason' => $this->reason,
            'salon_name' => $this->appointment->salon->name ?? '',
            'datetime' => $this->appointment->datetime_start?->format('d.m.Y H:i'),
            'message' => 'Ваша запись отменена',
        ];
    }

    public function toMail(object $notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('Запись отменена')
            ->line('Ваша запись была отменена.')
            ->line('Причина: ' . $this->reason)
            ->line('Салон: ' . ($this->appointment->salon->name ?? ''))
            ->line('Дата: ' . $this->appointment->datetime_start?->format('d.m.Y H:i'));
    }
}
