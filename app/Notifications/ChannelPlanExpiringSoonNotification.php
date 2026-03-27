<?php

declare(strict_types=1);


namespace App\Notifications;

use App\Domains\Content\Channels\Models\ChannelSubscriptionUsage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Уведомление о скором истечении подписки канала.
 */
final class ChannelPlanExpiringSoonNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly ChannelSubscriptionUsage $usage,
        private readonly int $daysLeft,
        private readonly string $correlationId,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Подписка вашего канала скоро истечёт')
            ->greeting('Здравствуйте!')
            ->line("Подписка вашего канала \"{$this->usage->channel->name}\" истечёт через {$this->daysLeft} дн.")
            ->line("Тарифный план: {$this->usage->plan?->name}")
            ->line("Дата истечения: {$this->usage->expires_at->format('d.m.Y H:i')}")
            ->action('Продлить подписку', url('/tenant/channels/' . $this->usage->channel_id . '/subscription'))
            ->line('При истечении подписки канал будет автоматически переведён на бесплатный тариф.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'channel_id' => $this->usage->channel_id,
            'channel_name' => $this->usage->channel->name,
            'plan_name' => $this->usage->plan?->name,
            'expires_at' => $this->usage->expires_at->toIso8601String(),
            'days_left' => $this->daysLeft,
            'correlation_id' => $this->correlationId,
        ];
    }
}
