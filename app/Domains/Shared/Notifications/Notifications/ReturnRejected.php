<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Notifications;

use App\Domains\Supermarket\Models\Return as ReturnModel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * ReturnRejected - Уведомление об отклонении возврата.
 */
class ReturnRejected extends Notification
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
            ->subject('Возврат отклонен - Заказ #' . $this->return->order_id)
            ->greeting('Здравствуйте!')
            ->line('К сожалению, ваш запрос на возврат для заказа #' . $this->return->order_id . ' был отклонен.')
            ->when($this->return->reject_reason, function (MailMessage $message) {
                return $message->line('Причина: ' . $this->return->reject_reason);
            })
            ->line('Если у вас есть вопросы, пожалуйста, свяжитесь с нашей службой поддержки.')
            ->action('Просмотреть детали', url('/filament/admin/resources/returns/' . $this->return->id))
            ->line('Спасибо за понимание.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'return_id' => $this->return->id,
            'order_id' => $this->return->order_id,
            'status' => $this->return->status,
            'reject_reason' => $this->return->reject_reason,
            'message' => 'Возврат для заказа #' . $this->return->order_id . ' отклонен',
        ];
    }
}
