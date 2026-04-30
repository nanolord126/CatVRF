<?php

declare(strict_types=1);

namespace App\Notifications\Security;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class EmployeeRevokedOwnerNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $employeeName,
        public readonly string $employeeEmail,
        public readonly string $revokedBy,
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
            ->subject('Employee Access Revoked')
            ->line("Employee {$this->employeeName} ({$this->employeeEmail}) access has been revoked.")
            ->line("Revoked by: {$this->revokedBy}")
            ->line("Reason: {$this->reason}")
            ->line("Revoked at: {$this->revokedAt->format('Y-m-d H:i:s')}")
            ->action('View Employees', url('/admin/employees'));
    }

    public function toArray($notifiable): array
    {
        return [
            'employee_name' => $this->employeeName,
            'employee_email' => $this->employeeEmail,
            'revoked_by' => $this->revokedBy,
            'reason' => $this->reason,
            'revoked_at' => $this->revokedAt->toIso8601String(),
        ];
    }
}
