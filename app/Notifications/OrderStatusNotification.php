<?php

declare(strict_types=1);

namespace App\Notifications;

use Carbon\CarbonImmutable;

use App\Models\Order;
use Illuminate\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

final class OrderStatusNotification extends Notification
{
    use Queueable;

    private readonly Order $order;

    private readonly string $status;

    private readonly string $correlationId;

    public function __construct(Order $order, string $status, string $correlationId)
    {
        $this->order = $order;
        $this->status = $status;
        $this->correlationId = $correlationId;
    }

    /**
     * Get the notification's delivery channels
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification
     */
    public function toMail(object $notifiable): MailMessage
    {
        $statusText = match ($this->status) {
            'confirmed' => 'подтвержден',
            'processing' => 'обрабатывается',
            'completed' => 'завершен',
            'cancelled' => 'отменен',
            default => $this->status,
        };

        return (new MailMessage())
            ->subject("Заказ #{$this->order->id} {$statusText}")
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Ваш заказ #{$this->order->id} был {$statusText}.")
            ->line("Сумма заказа: {$this->order->total_price} ₽")
            ->action('Просмотреть заказ', url("/orders/{$this->order->id}"))
            ->line('Спасибо за использование CatVRF!');
    }

    /**
     * Get the database representation of the notification
     */
    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $statusText = match ($this->status) {
            'confirmed' => 'подтвержден',
            'processing' => 'обрабатывается',
            'completed' => 'завершен',
            'cancelled' => 'отменен',
            default => $this->status,
        };

        return new DatabaseMessage(
            data: [
                'order_id' => $this->order->id,
                'order_uuid' => $this->order->uuid,
                'status' => $this->status,
                'message' => "Заказ #{$this->order->id} {$statusText}",
                'url' => "/orders/{$this->order->id}",
                'correlation_id' => $this->correlationId,
                'created_at' => CarbonImmutable::now()->toIso8601String(),
            ]
        );
    }
}
