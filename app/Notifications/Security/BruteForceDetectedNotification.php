<?php

declare(strict_types=1);

namespace App\Notifications\Security;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class BruteForceDetectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $type,
        public readonly string $ipAddress,
        public readonly \DateTime $timestamp,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Security Alert: Brute Force Attempt Detected')
            ->line('We detected a brute force attempt on your account.')
            ->line("Type: {$this->type}")
            ->line("IP Address: {$this->ipAddress}")
            ->line("Time: {$this->timestamp->format('Y-m-d H:i:s')}")
            ->line('If this was not you, please change your password immediately.')
            ->action('Change Password', url('/settings/security'));
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => $this->type,
            'ip_address' => $this->ipAddress,
            'timestamp' => $this->timestamp->toIso8601String(),
        ];
    }
}
