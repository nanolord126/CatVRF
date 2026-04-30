<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\CooldownPeriod;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Cooldown Activated Notification
 * 
 * Notifies users when a cooldown period is activated on their account.
 */
final class CooldownActivatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly CooldownPeriod $cooldown
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $actionTypeLabel = $this->cooldown->getActionTypeEnum()->getLabel();
        $remainingTime = $this->cooldown->getRemainingTimeForHumans();

        return (new MailMessage)
            ->subject('Период охлаждения активирован')
            ->greeting('Уважаемый пользователь,')
            ->line('На вашем аккаунте активирован период охлаждения для безопасности.')
            ->line("Тип действия: {$actionTypeLabel}")
            ->line("Причина: {$this->cooldown->reason}")
            ->line("Период охлаждения истекает через: {$remainingTime}")
            ->line('Это мера безопасности для защиты вашего аккаунта от подозрительной активности.')
            ->line('Если вы не инициировали это действие, немедленно свяжитесь с поддержкой.')
            ->action('Просмотреть детали', url('/dashboard/security'))
            ->line('Спасибо за использование CatVRF.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'cooldown_id' => $this->cooldown->id,
            'action_type' => $this->cooldown->action_type,
            'action_type_label' => $this->cooldown->getActionTypeEnum()->getLabel(),
            'reason' => $this->cooldown->reason,
            'triggered_at' => $this->cooldown->triggered_at->toIso8601String(),
            'expires_at' => $this->cooldown->expires_at->toIso8601String(),
            'remaining_time' => $this->cooldown->getRemainingTimeForHumans(),
            'remaining_seconds' => $this->cooldown->getRemainingSeconds(),
        ];
    }
}
