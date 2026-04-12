<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Notifications;

use App\Domains\RealEstate\Models\ViewingAppointment;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Notification: viewing appointment reminder.
 * Sent N hours before the scheduled viewing.
 * Channels: mail + database.
 */
final class ViewingReminderNotification extends Notification
{
    public function __construct(
        private readonly ViewingAppointment $appointment,
    ) {}

    /**
     * Delivery channels for the notification.
     *
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Build the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $scheduledAt = $this->appointment->scheduled_at
            ? $this->appointment->scheduled_at->format('d.m.Y H:i')
            : 'уточняйте у агента';

        $address = $this->appointment->property?->address ?? 'уточняйте у агента';

        return (new MailMessage())
            ->subject('Напоминание о просмотре недвижимости — CatVRF')
            ->greeting('Добрый день, ' . $notifiable->name . '!')
            ->line('Напоминаем о предстоящем просмотре объекта недвижимости.')
            ->line('Дата и время: ' . $scheduledAt)
            ->line('Адрес: ' . $address)
            ->action('Подробнее на сайте', url('/realestate/viewing/' . $this->appointment->id))
            ->salutation('С уважением, команда CatVRF');
    }

    /**
     * Get the array representation of the notification (for database channel).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'viewing_reminder',
            'appointment_id' => $this->appointment->id,
            'scheduled_at'   => $this->appointment->scheduled_at?->toIso8601String(),
            'property_id'    => $this->appointment->property_id,
        ];
    }
}
