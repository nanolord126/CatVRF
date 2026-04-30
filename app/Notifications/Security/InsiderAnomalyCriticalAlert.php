<?php

declare(strict_types=1);

namespace App\Notifications\Security;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class InsiderAnomalyCriticalAlert extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $tenantName,
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
            ->subject('CRITICAL: High-Severity Insider Anomaly')
            ->line('CRITICAL ALERT: High-severity insider anomaly detected')
            ->line("Tenant: {$this->tenantName}")
            ->line("Staff: {$this->staffName} ({$this->staffEmail})")
            ->line("Action: {$this->actionType}")
            ->line('Anomaly Score: '.number_format($this->anomalyScore * 100, 1).'%')
            ->line("Severity: {$this->severity}")
            ->line("Detected at: {$this->detectedAt->format('Y-m-d H:i:s')}")
            ->action('Review Immediately', url('/admin/security/threats'))
            ->line('This requires immediate attention.');
    }

    public function toArray($notifiable): array
    {
        return [
            'tenant_name' => $this->tenantName,
            'staff_name' => $this->staffName,
            'staff_email' => $this->staffEmail,
            'action_type' => $this->actionType,
            'anomaly_score' => $this->anomalyScore,
            'severity' => $this->severity,
            'detected_at' => $this->detectedAt->toIso8601String(),
        ];
    }
}
