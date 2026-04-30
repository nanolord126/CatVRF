<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Notifications;

use App\Domains\Supermarket\Models\SupermarketOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OrderConfirmed extends Notification
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
            ->subject("Заказ №{$this->order->id} подтверждён")
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Ваш заказ №{$this->order->id} подтверждён продавцом.")
            ->line("Сумма: " . number_format($this->order->total_amount / 100, 2, ',', ' ') . ' ₽')
            ->action('Отследить заказ', url("/orders/{$this->order->uuid}"))
            ->line('Ожидайте готовности к доставке.');
    }

    private function toSellerMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Заказ №{$this->order->id} подтверждён")
            ->line("Вы подтвердили заказ №{$this->order->id}.")
            ->line("Начните сборку заказа.");
    }

    public function toDatabase($notifiable): array
    {
        $title = $this->recipientType === 'buyer' 
            ? 'Заказ подтверждён' 
            : 'Заказ принят';

        return [
            'order_id' => $this->order->id,
            'order_uuid' => $this->order->uuid,
            'vertical' => 'supermarket',
            'title' => $title,
            'message' => "Заказ №{$this->order->id}",
            'type' => 'order_confirmed',
        ];
    }
}
