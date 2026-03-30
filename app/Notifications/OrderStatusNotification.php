<?php declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class OrderStatusNotification extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Queueable;

        public readonly Order $order;
        public readonly string $status;
        public readonly string $correlationId;

        /**
         * @param Order $order
         * @param string $status
         * @param string $correlationId
         */
        public function __construct(Order $order, string $status, string $correlationId)
        {
            $this->order = $order;
            $this->status = $status;
            $this->correlationId = $correlationId;
        }

        /**
         * Get the notification's delivery channels
         * @return array
         */
        public function via(object $notifiable): array
        {
            return ['mail', 'database'];
        }

        /**
         * Get the mail representation of the notification
         * @return MailMessage
         */
        public function toMail(object $notifiable): MailMessage
        {
            $statusText = match ($this->status) {
                'created' => 'создан',
                'confirmed' => 'подтвержден',
                'processing' => 'обрабатывается',
                'completed' => 'завершен',
                'cancelled' => 'отменен',
                default => $this->status,
            };

            return (new MailMessage)
                ->subject("Заказ #{$this->order->id} {$statusText}")
                ->greeting("Здравствуйте, {$notifiable->name}!")
                ->line("Ваш заказ #{$this->order->id} был {$statusText}.")
                ->line("Сумма заказа: {$this->order->total_price} ₽")
                ->action('Просмотреть заказ', url("/orders/{$this->order->id}"))
                ->line('Спасибо за использование CatVRF!');
        }

        /**
         * Get the database representation of the notification
         * @return DatabaseMessage
         */
        public function toDatabase(object $notifiable): DatabaseMessage
        {
            $statusText = match ($this->status) {
                'created' => 'создан',
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
                    'created_at' => now()->toIso8601String(),
                ]
            );
        }
}
