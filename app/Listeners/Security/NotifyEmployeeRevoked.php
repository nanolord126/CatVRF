<?php

declare(strict_types=1);

namespace App\Listeners\Security;

use Psr\Log\LoggerInterface;

use Carbon\CarbonImmutable;

use App\Events\Security\EmployeeRevoked;
use Illuminate\Log\LogManager;
use App\Models\User;
use App\Notifications\Security\EmployeeAccessRevokedNotification;
use App\Notifications\Security\EmployeeRevokedAuditNotification;
use App\Notifications\Security\EmployeeRevokedOwnerNotification;

final class NotifyEmployeeRevoked
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(EmployeeRevoked $event): void
    {
        $this->log->channel('security')->$this->logger->info('Employee access revoked', [
            'employee_id' => $event->employee->id,
            'employee_email' => $event->employee->email,
            'tenant_id' => $event->tenant->id,
            'tenant_name' => $event->tenant->name,
            'revoked_by' => $event->revokedBy->id,
            'revoked_by_email' => $event->revokedBy->email,
            'reason' => $event->reason,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ]);

        // Notify employee
        try {
            $event->employee->notify(new EmployeeAccessRevokedNotification(
                tenantName: $event->tenant->name,
                reason: $event->reason,
                revokedAt: CarbonImmutable::now(),
            ));
        } catch (\Exception $e) {
            $this->log->error('Failed to send employee revocation notification', [
                'employee_id' => $event->employee->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Notify tenant owner
        $owner = $event->tenant->users()
            ->wherePivot('role', 'owner')
            ->wherePivot('is_active', true)
            ->first();

        if ($owner && $owner->id !== $event->revokedBy->id) {
            try {
                $owner->notify(new EmployeeRevokedOwnerNotification(
                    employeeName: $event->employee->getFullName(),
                    employeeEmail: $event->employee->email,
                    revokedBy: $event->revokedBy->getFullName(),
                    reason: $event->reason,
                    revokedAt: CarbonImmutable::now(),
                ));
            } catch (\Exception $e) {
                $this->log->error('Failed to send owner notification for employee revocation', [
                    'owner_id' => $owner->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Notify super-admins for audit trail
        $superAdmins = User::platformAdmins()->active()->get();
        foreach ($superAdmins as $admin) {
            try {
                $admin->notify(new EmployeeRevokedAuditNotification(
                    tenantName: $event->tenant->name,
                    employeeName: $event->employee->getFullName(),
                    employeeEmail: $event->employee->email,
                    revokedBy: $event->revokedBy->email,
                    reason: $event->reason,
                    revokedAt: CarbonImmutable::now(),
                ));
            } catch (\Exception $e) {
                $this->log->error('Failed to send admin audit notification for employee revocation', [
                    'admin_id' => $admin->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
