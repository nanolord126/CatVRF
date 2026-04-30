<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Notifications;

use App\Domains\Supermarket\Models\Return as ReturnModel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

final class ReturnCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ReturnModel $return,
        public string $recipientType // 'buyer' or 'seller'
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $subject = $this->recipientType === 'buyer'
            ? 'Ваша заявка на возврат создана'
            : 'Новая заявка на возврат';

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting($this->recipientType === 'buyer' ? 'Здравствуйте!' : 'Уважаемый партнер!');

        if ($this->recipientType === 'buyer') {
            $message->line('Ваша заявка на возврат заказа #' . $this->return->order_id . ' успешно создана.')
                ->line('Сумма возврата: ' . number_format($this->return->refund_amount, 2, ',', ' ') . ' ₽')
                ->line('Статус: ' . $this->return->status)
                ->line('Мы рассмотрим вашу заявку в ближайшее время.')
                ->action('Отследить статус', route('buyer.returns.show', $this->return->id));
        } else {
            $message->line('Получена новая заявка на возврат.')
                ->line('Заказ #' . $this->return->order_id)
                ->line('Сумма возврата: ' . number_format($this->return->refund_amount, 2, ',', ' ') . ' ₽')
                ->line('Причина: ' . $this->return->reason_type)
                ->line('Комментарий: ' . ($this->return->reason_comment ?? 'Нет'))
                ->action('Рассмотреть заявку', route('filament.admin.resources.returns.edit', $this->return->id));
        }

        return $message->line('Спасибо за использование CatVRF!');
    }

    public function toDatabase(User $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'return_id' => $this->return->id,
            'order_id' => $this->return->order_id,
            'refund_amount' => $this->return->refund_amount,
            'status' => $this->return->status,
            'reason_type' => $this->return->reason_type,
            'recipient_type' => $this->recipientType,
            'created_at' => $this->return->created_at->toIso8601String(),
        ]);
    }
}
}
