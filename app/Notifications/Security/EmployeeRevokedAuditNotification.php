<?php

declare(strict_types=1);

namespace App\Notifications\Security;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class EmployeeRevokedAuditNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $tenantName,
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
            ->subject('[AUDIT] Employee Access Revoked')
            ->line('AUDIT ALERT: Employee access revocation')
            ->line("Tenant: {$this->tenantName}")
            ->line("Employee: {$this->employeeName} ({$this->employeeEmail})")
            ->line("Revoked by: {$this->revokedBy}")
            ->line("Reason: {$this->reason}")
            ->line("Revoked at: {$this->revokedAt->format('Y-m-d H:i:s')}")
            ->action('View Audit Log', url('/admin/audit'));
    }

    public function toArray($notifiable): array
    {
        return [
            'tenant_name' => $this->tenantName,
            'employee_name' => $this->employeeName,
            'employee_email' => $this->employeeEmail,
            'revoked_by' => $this->revokedBy,
            'reason' => $this->reason,
            'revoked_at' => $this->revokedAt->toIso8601String(),
        ];
    }
}
