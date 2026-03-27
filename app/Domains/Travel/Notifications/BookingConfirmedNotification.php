<?php

declare(strict_types=1);

namespace App\Domains\Travel\Notifications;

use App\Domains\Travel\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * КАНОН 2026: Booking Confirmation Notification (Travel-special).
 * Слой 9: События и уведомления.
 */
final class BookingConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Booking $booking,
        private readonly string $correlationId
    ) {}

    /**
     * Каналы доставки.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Письмо-подтверждение.
     */
    public function toMail(object $notifiable): MailMessage
    {
        Log::channel('audit')->info('Sending booking confirmation email', [
            'booking_id' => $this->booking->id,
            'user_id' => $notifiable->id,
            'correlation_id' => $this->correlationId
        ]);

        return (new MailMessage)
            ->subject('Ваше путешествие подтверждено!')
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Ваше бронирование №{$this->booking->id} успешно оплачено.")
            ->line("Объект: " . $this->booking->bookable->name)
            ->line("Дата выезда: " . ($this->booking->bookable->departure_date ?? 'Не указана'))
            ->line("Количество мест: {$this->booking->slots_count}")
            ->action('Посмотреть детали', url("/travel/bookings/{$this->booking->id}"))
            ->line('Спасибо, что выбрали наш сервис!')
            ->with(['correlation_id' => $this->correlationId]);
    }

    /**
     * Уведомление в БД.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'message' => "Ваше путешествие {$this->booking->bookable->name} подтверждено.",
            'total_price' => $this->booking->total_price,
            'correlation_id' => $this->correlationId,
            'vertical' => 'travel'
        ];
    }
}
