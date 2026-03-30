<?php declare(strict_types=1);

namespace App\Domains\Beauty\Notifications;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AppointmentReminderNotification extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Queueable;

        public function __construct(
            private readonly Appointment $appointment,
            private readonly int $hoursBeforeAppointment,
            private readonly string $correlationId,
        ) {
        }

        public function via(object $notifiable): array
        {
            return ['mail', 'database'];
        }

        public function toMail(object $notifiable): MailMessage
        {
            $salonName = (string) ($this->appointment->salon?->name ?? 'Салон');
            $serviceName = (string) ($this->appointment->service?->name ?? 'Услуга');
            $masterName = (string) ($this->appointment->master?->full_name ?? 'Мастер');
            $dateTime = (string) ($this->appointment->datetime_start?->format('d.m.Y H:i') ?? 'уточняется');

            return (new MailMessage)
                ->subject('Напоминание о записи')
                ->greeting('Здравствуйте!')
                ->line("Напоминание: до вашей записи осталось {$this->hoursBeforeAppointment} ч.")
                ->line("Салон: {$salonName}")
                ->line("Услуга: {$serviceName}")
                ->line("Мастер: {$masterName}")
                ->line("Дата и время: {$dateTime}")
                ->line('Correlation ID: ' . $this->correlationId);
        }

        public function toArray(object $notifiable): array
        {
            return [
                'appointment_id' => $this->appointment->id,
                'hours_before' => $this->hoursBeforeAppointment,
                'salon_name' => $this->appointment->salon?->name,
                'service_name' => $this->appointment->service?->name,
                'master_name' => $this->appointment->master?->full_name,
                'scheduled_for' => $this->appointment->datetime_start?->toDateTimeString(),
                'correlation_id' => $this->correlationId,
                'vertical' => 'beauty',
            ];
        }
}
