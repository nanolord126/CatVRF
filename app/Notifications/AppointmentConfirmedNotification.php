<?php declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AppointmentConfirmedNotification extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Queueable;

        public function __construct(
            private readonly Appointment $appointment,
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
                'master_name' => $this->appointment->master->full_name ?? '',
                'service_name' => $this->appointment->service->name ?? '',
                'datetime' => $this->appointment->datetime_start?->format('d.m.Y H:i'),
                'message' => 'Ваша запись подтверждена',
            ];
        }

        public function toMail(object $notifiable): \Illuminate\Notifications\Messages\MailMessage
        {
            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->subject('Запись подтверждена')
                ->line('Ваша запись подтверждена!')
                ->line('Мастер: ' . ($this->appointment->master->full_name ?? ''))
                ->line('Услуга: ' . ($this->appointment->service->name ?? ''))
                ->line('Дата и время: ' . $this->appointment->datetime_start?->format('d.m.Y H:i'))
                ->line('Адрес: ' . ($this->appointment->salon->address ?? ''));
        }
}
