<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Notifications;

use App\Domains\Supermarket\Models\SupermarketOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OrderReadyForDelivery extends Notification
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
            ->subject("Заказ №{$this->order->id} готов к доставке")
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Ваш заказ №{$this->order->id} готов и передан в доставку.")
            ->action('Отследить заказ', url("/orders/{$this->order->uuid}"))
            ->line('Ожидайте курьера.');
    }

    private function toSellerMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Заказ №{$this->order->id} передан в доставку")
            ->line("Заказ №{$this->order->id} передан курьеру.");
    }

    public function toDatabase($notifiable): array
    {
        $title = $this->recipientType === 'buyer' 
            ? 'Заказ готов к доставке' 
            : 'Заказ передан в доставку';

        return [
            'order_id' => $this->order->id,
            'order_uuid' => $this->order->uuid,
            'vertical' => 'supermarket',
            'title' => $title,
            'message' => "Заказ №{$this->order->id}",
            'type' => 'order_ready',
        ];
    }
}
