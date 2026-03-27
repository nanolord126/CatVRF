<?php

declare(strict_types=1);

namespace App\Domains\Tickets\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Domains\Tickets\Models\Ticket;

/**
 * КАНОН 2026: Уведомление о покупке билета.
 */
final class TicketPurchasedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private readonly string $correlationId;

    public function __construct(
        private readonly Ticket $ticket,
        ?string $correlationId = null
    ) {
        $this->correlationId = $correlationId ?? $ticket->correlation_id;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Ваш билет на мероприятие: {$this->ticket->event->title}")
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Поздравляем с успешной покупкой билета.")
            ->line("Мероприятие: {$this->ticket->event->title}")
            ->line("Тип билета: {$this->ticket->ticketType->name}")
            ->line("QR-код для входа приложен.")
            ->action('Посмотреть билет', url("/tickets/{$this->ticket->uuid}"))
            ->line('Correlation ID: ' . $this->correlationId);
    }

    public function toArray($notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'event_title' => $this->ticket->event->title,
            'qr_code' => $this->ticket->qr_code,
            'correlation_id' => $this->correlationId,
        ];
    }
}
