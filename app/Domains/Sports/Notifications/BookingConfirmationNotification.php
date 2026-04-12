<?php declare(strict_types=1);

namespace App\Domains\Sports\Notifications;

use App\Domains\Sports\Models\Booking;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Notification: sports booking confirmation.
 * Sent immediately after a booking is confirmed.
 * Channels: mail + database.
 */
final class BookingConfirmationNotification extends Notification
{
    public function __construct(
        private readonly Booking $booking,
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
        $scheduledAt = $this->booking->scheduled_at
            ? $this->booking->scheduled_at->format('d.m.Y H:i')
            : 'уточняйте';

        $serviceName = $this->booking->service?->name ?? 'Услуга';

        return (new MailMessage())
            ->subject('Бронирование подтверждено — CatVRF Sports')
            ->greeting('Добрый день, ' . $notifiable->name . '!')
            ->line('Ваше бронирование успешно подтверждено.')
            ->line('Услуга: ' . $serviceName)
            ->line('Дата и время: ' . $scheduledAt)
            ->action('Просмотреть бронирование', url('/sports/booking/' . $this->booking->id))
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
            'type'         => 'booking_confirmation',
            'booking_id'   => $this->booking->id,
            'scheduled_at' => $this->booking->scheduled_at?->toIso8601String(),
            'service_id'   => $this->booking->service_id,
        ];
    }
}
