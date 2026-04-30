<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Notifications;

use App\Domains\Supermarket\Models\SupermarketOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OrderCancelled extends Notification
{
    use Queueable;

    public function __construct(
        public readonly SupermarketOrder $order,
        public readonly string $recipientType
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        if ($this->recipientType === 'buyer') {
            return $this->toBuyerMail($notifiable);
        }

        return $this->toSellerMail($notifiable);
    }

    private function toBuyerMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Заказ №{$this->order->id} отменён")
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("К сожалению, заказ №{$this->order->id} был отменён.")
            ->line('Если оплата была произведена, средства будут возвращены.')
            ->action('Сделать новый заказ', url('/supermarket'));
    }

    private function toSellerMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Заказ №{$this->order->id} отменён")
            ->line("Заказ №{$this->order->id} был отменён.")
            ->line('Пожалуйста, проверьте детали заказа.');
    }

    public function toDatabase($notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_uuid' => $this->order->uuid,
            'vertical' => 'supermarket',
            'title' => 'Заказ отменён',
            'message' => "Заказ №{$this->order->id}",
            'type' => 'order_cancelled',
        ];
    }
}
