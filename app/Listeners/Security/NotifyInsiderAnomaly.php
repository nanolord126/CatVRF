<?php

declare(strict_types=1);

namespace App\Listeners\Security;

use Carbon\CarbonImmutable;

use App\Events\Security\InsiderAnomalyDetected;
use Illuminate\Log\LogManager;
use App\Models\User;
use App\Notifications\Security\InsiderAnomalyCriticalAlert;
use App\Notifications\Security\InsiderAnomalyNotification;

final class NotifyInsiderAnomaly
{
    public function handle(InsiderAnomalyDetected $event): void
    {
        $this->log->channel('security')->warning('Insider anomaly detected', [
            'staff_id' => $event->staff->id,
            'staff_email' => $event->staff->email,
            'tenant_id' => $event->tenant->id,
            'tenant_name' => $event->tenant->name,
            'action_type' => $event->actionType,
            'anomaly_score' => $event->anomalyScore,
            'severity' => $event->severity,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ]);

        // Notify tenant owner
        $owner = $event->tenant->users()
            ->wherePivot('role', 'owner')
            ->wherePivot('is_active', true)
            ->first();

        if ($owner) {
            try {
                $owner->notify(new InsiderAnomalyNotification(
                    staffName: $event->staff->getFullName(),
                    staffEmail: $event->staff->email,
                    actionType: $event->actionType,
                    anomalyScore: $event->anomalyScore,
                    severity: $event->severity,
                    detectedAt: CarbonImmutable::now(),
                ));
            } catch (\Exception $e) {
                $this->log->error('Failed to send insider anomaly notification to owner', [
                    'owner_id' => $owner->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Notify super-admins for critical/high severity
        if (in_array($event->severity, ['critical', 'high'], true)) {
            $superAdmins = User::platformAdmins()->active()->get();
            foreach ($superAdmins as $admin) {
                try {
                    $admin->notify(new InsiderAnomalyCriticalAlert(
                        tenantName: $event->tenant->name,
                        staffName: $event->staff->getFullName(),
                        staffEmail: $event->staff->email,
                        actionType: $event->actionType,
                        anomalyScore: $event->anomalyScore,
                        severity: $event->severity,
                        detectedAt: CarbonImmutable::now(),
                    ));
                } catch (\Exception $e) {
                    $this->log->error('Failed to send critical insider anomaly alert to admin', [
                        'admin_id' => $admin->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}
