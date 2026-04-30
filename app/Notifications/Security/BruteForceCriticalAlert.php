<?php declare(strict_types=1);

namespace App\Notifications\Security;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class BruteForceCriticalAlert extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $type,
        public readonly string $ipAddress,
        public readonly ?string $email,
        public readonly array $metadata,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('CRITICAL: Brute Force Attack Blocked')
            ->line('A critical brute force attack was blocked by the security system.')
            ->line("Type: {$this->type}")
            ->line("IP Address: {$this->ipAddress}")
            ->line("Email: {$this->email ?? 'N/A'}")
            ->line("Details: " . json_encode($this->metadata))
            ->action('View Security Dashboard', url('/admin/security'))
            ->line('Please review this incident immediately.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => $this->type,
            'ip_address' => $this->ipAddress,
            'email' => $this->email,
            'metadata' => $this->metadata,
        ];
    }
}
