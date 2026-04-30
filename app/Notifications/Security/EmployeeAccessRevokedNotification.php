<?php

declare(strict_types=1);

namespace App\Notifications\Security;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class EmployeeAccessRevokedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $tenantName,
        public readonly string $reason,
        public readonly \DateTime $revokedAt,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Access Revoked: '.$this->tenantName)
            ->line('Your access to '.$this->tenantName.' has been revoked.')
            ->line("Reason: {$this->reason}")
            ->line("Revoked at: {$this->revokedAt->format('Y-m-d H:i:s')}")
            ->line('If you believe this is an error, please contact your tenant administrator.')
            ->action('Contact Support', url('/support'));
    }

    public function toArray($notifiable): array
    {
        return [
            'tenant_name' => $this->tenantName,
            'reason' => $this->reason,
            'revoked_at' => $this->revokedAt->toIso8601String(),
        ];
    }
}
