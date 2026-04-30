<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Notifications;

use App\Domains\Supermarket\Models\SupermarketOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OrderDelivered extends Notification
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
            ->subject("Заказ №{$this->order->id} доставлен")
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Ваш заказ №{$this->order->id} успешно доставлен.")
            ->line("Сумма: " . number_format($this->order->total_amount / 100, 2, ',', ' ') . ' ₽')
            ->action('Оставить отзыв', url("/orders/{$this->order->uuid}/review"))
            ->line('Спасибо за покупку!');
    }

    private function toSellerMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Заказ №{$this->order->id} доставлен")
            ->line("Заказ №{$this->order->id} успешно доставлен покупателю.")
            ->line('Кэшбек будет начислен автоматически.');
    }

    public function toDatabase($notifiable): array
    {
        $title = $this->recipientType === 'buyer' 
            ? 'Заказ доставлен' 
            : 'Доставка завершена';

        return [
            'order_id' => $this->order->id,
            'order_uuid' => $this->order->uuid,
            'vertical' => 'supermarket',
            'title' => $title,
            'message' => "Заказ №{$this->order->id}",
            'type' => 'order_delivered',
        ];
    }
}
