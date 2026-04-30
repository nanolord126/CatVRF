<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Notifications;

use App\Domains\Supermarket\Models\SupermarketOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SellerNewOrder extends Notification
{
    use Queueable;

    public function __construct(
        public readonly SupermarketOrder $order
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Новый заказ №{$this->order->id}")
            ->greeting("Новый заказ!")
            ->line("Покупатель: {$this->order->buyer->name}")
            ->line("Сумма: " . number_format($this->order->total_amount / 100, 2, ',', ' ') . ' ₽')
            ->line("Подвертикаль: {$this->order->sub_vertical}")
            ->action('Посмотреть заказ', url("/filament/supermarket/orders/{$this->order->id}"))
            ->line('Примите заказ в ближайшее время.');
    }

    public function toDatabase($notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_uuid' => $this->order->uuid,
            'vertical' => 'supermarket',
            'title' => 'Новый заказ',
            'message' => "Заказ №{$this->order->id} от {$this->order->buyer->name}",
            'type' => 'new_order',
        ];
    }
}
