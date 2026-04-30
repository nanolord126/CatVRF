<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Notifications;

use App\Domains\Supermarket\Models\SupermarketOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class BuyerOrderCreated extends Notification
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
            ->subject('Заказ оформлен!')
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Ваш заказ №{$this->order->id} успешно оформлен.")
            ->line("Сумма: " . number_format($this->order->total_amount / 100, 2, ',', ' ') . ' ₽')
            ->line("Статус: Ожидает подтверждения")
            ->action('Посмотреть заказ', url("/orders/{$this->order->uuid}"))
            ->line('Спасибо за заказ!');
    }

    public function toDatabase($notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_uuid' => $this->order->uuid,
            'vertical' => 'supermarket',
            'title' => 'Заказ оформлен',
            'message' => "Заказ №{$this->order->id} на сумму " . number_format($this->order->total_amount / 100, 2, ',', ' ') . ' ₽',
            'type' => 'order_created',
        ];
    }
}
