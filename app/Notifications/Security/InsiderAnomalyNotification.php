<?php

declare(strict_types=1);

namespace App\Notifications\Security;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class InsiderAnomalyNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $staffName,
        public readonly string $staffEmail,
        public readonly string $actionType,
        public readonly float $anomalyScore,
        public readonly string $severity,
        public readonly \DateTime $detectedAt,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Security Alert: Insider Anomaly Detected')
            ->line('An insider threat anomaly has been detected.')
            ->line("Staff: {$this->staffName} ({$this->staffEmail})")
            ->line("Action: {$this->actionType}")
            ->line('Anomaly Score: '.number_format($this->anomalyScore * 100, 1).'%')
            ->line("Severity: {$this->severity}")
            ->line("Detected at: {$this->detectedAt->format('Y-m-d H:i:s')}")
            ->action('Review Anomaly', url('/admin/security/threats'));
    }

    public function toArray($notifiable): array
    {
        return [
            'staff_name' => $this->staffName,
            'staff_email' => $this->staffEmail,
            'action_type' => $this->actionType,
            'anomaly_score' => $this->anomalyScore,
            'severity' => $this->severity,
            'detected_at' => $this->detectedAt->toIso8601String(),
        ];
    }
}
