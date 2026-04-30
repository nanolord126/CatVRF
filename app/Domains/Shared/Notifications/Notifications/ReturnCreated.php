<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Notifications;

use App\Domains\Supermarket\Models\Return as ReturnModel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * ReturnCreated - Уведомление о создании возврата.
 */
class ReturnCreated extends Notification
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
        $reasonText = match ($this->return->reason_type) {
            'spoiled' => 'Испорчено',
            'wrong_item' => 'Не тот товар',
            'changed_mind' => 'Передумал',
            'damaged' => 'Повреждено',
            'expired' => 'Просрочено',
            'other' => 'Другое',
            default => $this->return->reason_type,
        };

        return (new MailMessage)
            ->subject('Создан запрос на возврат заказа #' . $this->return->order_id)
            ->greeting('Здравствуйте!')
            ->line('Ваш запрос на возврат для заказа #' . $this->return->order_id . ' успешно создан.')
            ->line('Причина возврата: ' . $reasonText)
            ->line('Сумма возврата: ' . number_format($this->return->total_amount / 100, 2) . ' ₽')
            ->line('Статус: В ожидании рассмотрения')
            ->line('Мы рассмотрим ваш запрос в ближайшее время.')
            ->action('Просмотреть возврат', url('/filament/admin/resources/returns/' . $this->return->id))
            ->line('Спасибо, что пользуетесь нашим сервисом!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'return_id' => $this->return->id,
            'order_id' => $this->return->order_id,
            'status' => $this->return->status,
            'reason_type' => $this->return->reason_type,
            'total_amount' => $this->return->total_amount,
            'message' => 'Создан запрос на возврат заказа #' . $this->return->order_id,
        ];
    }
}
