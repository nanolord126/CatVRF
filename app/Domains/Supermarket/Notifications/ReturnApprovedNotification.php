<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Notifications;

use App\Domains\Supermarket\Models\Return as ReturnModel;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

final class ReturnApprovedNotification extends Notification
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
            ->subject('Ваша заявка на возврат одобрена')
            ->greeting('Здравствуйте!')
            ->line('Ваша заявка на возврат заказа #' . $this->return->order_id . ' одобрена.')
            ->line('Сумма возврата: ' . number_format($this->return->refund_amount, 2, ',', ' ') . ' ₽')
            ->line('Деньги будут возвращены на вашу карту в течение 3-5 рабочих дней.')
            ->action('Отследить статус', route('buyer.returns.show', $this->return->id))
            ->line('Спасибо за использование CatVRF!');
    }

    public function toDatabase(User $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'return_id' => $this->return->id,
            'order_id' => $this->return->order_id,
            'refund_amount' => $this->return->refund_amount,
            'status' => $this->return->status,
            'created_at' => $this->return->updated_at->toIso8601String(),
        ]);
    }
}
