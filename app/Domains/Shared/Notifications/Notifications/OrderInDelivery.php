<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Notifications;

use App\Domains\Supermarket\Models\SupermarketOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OrderInDelivery extends Notification
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
            ->subject("Заказ №{$this->order->id} в пути")
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Курьер уже в пути с вашим заказом №{$this->order->id}.")
            ->action('Отследить заказ', url("/orders/{$this->order->uuid}"))
            ->line('Будьте готовы к встрече.');
    }

    public function toDatabase($notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_uuid' => $this->order->uuid,
            'vertical' => 'supermarket',
            'title' => 'Заказ в пути',
            'message' => "Курьер в пути с заказом №{$this->order->id}",
            'type' => 'order_in_delivery',
        ];
    }
}
