<?php declare(strict_types=1);

namespace App\Mail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AppointmentReminderMail extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Queueable;
        use SerializesModels;

        public function __construct(
            public readonly Appointment $appointment,
        ) {
        /**
         * Инициализировать класс
         */
        public function __construct()
        {
            // TODO: инициализация
        }
    }

        public function envelope(): Envelope
        {
            return new Envelope(
                subject: 'Напоминание о записи',
            );
        }

        public function content(): Content
        {
            return new Content(
                view: 'emails.appointment-reminder',
                with: [
                    'masterName' => $this->appointment->master->full_name ?? '',
                    'serviceName' => $this->appointment->service->name ?? '',
                    'datetime' => $this->appointment->datetime_start?->format('d.m.Y H:i'),
                    'salonAddress' => $this->appointment->salon->address ?? '',
                ],
            );
        }
}
