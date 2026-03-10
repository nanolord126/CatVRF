<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class NewDeviceDetectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected array $deviceInfo) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('New Login Detected - Security Alert 2026')
                    ->greeting("Hello, {$notifiable->name}")
                    ->line("A new login from device: " . $this->deviceInfo['browser'] . " (IP: " . $this->deviceInfo['ip'] . ")")
                    ->line("If this was not you, please block your account immediately.")
                    ->action('Manage Devices', url('/filament/tenant/profile'))
                    ->line('Safety First in Zero Trust Ecosystem.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'security_alert',
            'message' => 'New device detected',
            'device' => $this->deviceInfo
        ];
    }
}
