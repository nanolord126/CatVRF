<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Notifications;

use App\Domains\Supermarket\Models\Return as ReturnModel;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

final class ReturnRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ReturnModel $return
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Ваша заявка на возврат отклонена')
            ->greeting('Здравствуйте!')
            ->line('К сожалению, ваша заявка на возврат заказа #' . $this->return->order_id . ' была отклонена.')
            ->line('Причина: ' . $this->return->reject_reason ?? 'Не указана')
            ->line('Если вы считаете это ошибкой, пожалуйста, свяжитесь с нашей службой поддержки.')
            ->action('Связаться с поддержкой', route('contact'))
            ->line('Спасибо за использование CatVRF!');
    }

    public function toDatabase(User $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'return_id' => $this->return->id,
            'order_id' => $this->return->order_id,
            'refund_amount' => $this->return->refund_amount,
            'status' => $this->return->status,
            'reject_reason' => $this->return->reject_reason,
            'created_at' => $this->return->updated_at->toIso8601String(),
        ]);
    }
}
