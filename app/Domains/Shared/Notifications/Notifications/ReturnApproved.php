<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Notifications;

use App\Domains\Supermarket\Models\Return as ReturnModel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * ReturnApproved - Уведомление об одобрении возврата.
 */
class ReturnApproved extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ReturnModel $return
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Возврат одобрен - Заказ #' . $this->return->order_id)
            ->greeting('Здравствуйте!')
            ->line('Ваш запрос на возврат для заказа #' . $this->return->order_id . ' был одобрен.')
            ->line('Сумма возврата: ' . number_format($this->return->refund_amount / 100, 2) . ' ₽')
            ->line('Деньги будут возвращены на ваш баланс в ближайшее время.')
            ->action('Просмотреть детали', url('/filament/admin/resources/returns/' . $this->return->id))
            ->line('Спасибо за ваше терпение!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'return_id' => $this->return->id,
            'order_id' => $this->return->order_id,
            'status' => $this->return->status,
            'refund_amount' => $this->return->refund_amount,
            'message' => 'Возврат для заказа #' . $this->return->order_id . ' одобрен',
        ];
    }
}
