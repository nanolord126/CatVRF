<?php

declare(strict_types=1);

namespace App\Domains\Sports\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class BookingConfirmedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $bookingId,
        public readonly int $venueId,
        public readonly string $slotStart,
        public readonly string $slotEnd,
        public readonly string $bookingType,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Sports Booking is Confirmed')
            ->greeting('Hello!')
            ->line('Your sports booking has been confirmed successfully.')
            ->line('Booking Details:')
            ->line("- Booking ID: {$this->bookingId}")
            ->line("- Venue ID: {$this->venueId}")
            ->line("- Date & Time: {$this->slotStart} - {$this->slotEnd}")
            ->line("- Type: {$this->bookingType}")
            ->action('View Booking', url("/sports/bookings/{$this->bookingId}"))
            ->line('Thank you for using our platform!');
    }

    public function toArray($notifiable): array
    {
        return [
            'booking_id' => $this->bookingId,
            'venue_id' => $this->venueId,
            'slot_start' => $this->slotStart,
            'slot_end' => $this->slotEnd,
            'booking_type' => $this->bookingType,
            'message' => 'Your sports booking has been confirmed.',
        ];
    }
}
