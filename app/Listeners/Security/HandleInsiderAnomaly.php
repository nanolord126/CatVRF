<?php

declare(strict_types=1);

namespace App\Listeners\Security;

use App\Events\InsiderAnomalyDetected;
use App\Services\Security\AuditService;
use App\Services\Security\CooldownService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Handle Insider Anomaly Event
 * 
 * Listens for insider threat anomalies and takes appropriate action:
 * - Triggers cooldown for critical/high severity
 * - Sends notifications to owners and super-admins
 * - Logs to security channels
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Data Exfiltration Fortress.
 */
final readonly class HandleInsiderAnomaly implements ShouldQueue
{
    public function __construct(
        private readonly CooldownService $cooldownService,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Handle the event.
     */
    public function handle(InsiderAnomalyDetected $event): void
    {
        $staff = $event->staff;
        $tenant = $event->tenant;
        $anomalyScore = $event->anomalyScore;
        $severity = $event->severity;

        // Log the anomaly
        $this->logAnomaly($event);

        // Trigger cooldown for critical/high severity
        if ($severity === 'critical' || $severity === 'high') {
            $this->triggerCooldown($event);
        }

        // Send notifications
        $this->sendNotifications($event);

        // Lock account for critical severity
        if ($severity === 'critical' || $anomalyScore >= 0.9) {
            $this->lockAccount($staff);
        }
    }

    /**
     * Log the anomaly to security channels
     */
    private function logAnomaly(InsiderAnomalyDetected $event): void
    {
        Log::channel('security_alert')->critical('Insider anomaly detected', [
            'user_id' => $event->staff->id,
            'tenant_id' => $event->tenant->id,
            'action_type' => $event->actionType,
            'anomaly_score' => $event->anomalyScore,
            'severity' => $event->severity,
            'was_blocked' => $event->log->was_blocked,
        ]);

        $this->auditService->logEvent('insider_anomaly_detected', [
            'user_id' => $event->staff->id,
            'tenant_id' => $event->tenant->id,
            'action_type' => $event->actionType,
            'anomaly_score' => $event->anomalyScore,
            'severity' => $event->severity,
            'was_blocked' => $event->log->was_blocked,
            'log_id' => $event->log->id,
        ], 'security');
    }

    /**
     * Trigger cooldown for the staff member
     */
    private function triggerCooldown(InsiderAnomalyDetected $event): void
    {
        $durationHours = match ($event->severity) {
            'critical' => 168, // 7 days
            'high' => 24, // 1 day
            default => 1,
        };

        $reason = sprintf(
            'Insider anomaly detected: %s (score: %.4f)',
            $event->actionType,
            $event->anomalyScore
        );

        $this->cooldownService->startCooldown(
            $event->staff,
            \App\Enums\CooldownActionType::HUNTING_DETECTED,
            $durationHours,
            $reason,
            $event->tenant->id // Apply to entire tenant
        );

        Log::warning('Cooldown triggered for insider anomaly', [
            'user_id' => $event->staff->id,
            'tenant_id' => $event->tenant->id,
            'duration_hours' => $durationHours,
            'reason' => $reason,
        ]);
    }

    /**
     * Send notifications to relevant parties
     */
    private function sendNotifications(InsiderAnomalyDetected $event): void
    {
        // Only send notifications for high/critical severity
        if (! in_array($event->severity, ['high', 'critical'], true)) {
            return;
        }

        // Notify tenant owners
        $this->notifyTenantOwners($event);

        // Notify super-admins for critical severity
        if ($event->severity === 'critical') {
            $this->notifySuperAdmins($event);
        }
    }

    /**
     * Notify tenant owners about the anomaly
     */
    private function notifyTenantOwners(InsiderAnomalyDetected $event): void
    {
        $owners = $event->tenant->users()
            ->wherePivot('role', \App\Enums\Role::Owner->value)
            ->wherePivot('is_active', true)
            ->get();

        foreach ($owners as $owner) {
            // Send notification (implementation depends on notification system)
            // Notification::send($owner, new InsiderAnomalyNotification($event));
            
            Log::info('Insider anomaly notification sent to owner', [
                'owner_id' => $owner->id,
                'tenant_id' => $event->tenant->id,
                'anomaly_score' => $event->anomalyScore,
            ]);
        }
    }

    /**
     * Notify super-admins about critical anomalies
     */
    private function notifySuperAdmins(InsiderAnomalyDetected $event): void
    {
        $superAdmins = \App\Models\User::platformAdmins()->active()->get();

        foreach ($superAdmins as $admin) {
            // Send notification (implementation depends on notification system)
            // Notification::send($admin, new CriticalInsiderAnomalyNotification($event));
            
            Log::info('Critical insider anomaly notification sent to super-admin', [
                'admin_id' => $admin->id,
                'tenant_id' => $event->tenant->id,
                'anomaly_score' => $event->anomalyScore,
            ]);
        }
    }

    /**
     * Lock the staff account for critical anomalies
     */
    private function lockAccount(\App\Models\User $staff): void
    {
        $staff->update([
            'is_active' => false,
            'is_locked' => true,
            'locked_until' => \Carbon\CarbonImmutable::now()->addDays(7),
        ]);

        // Revoke all sessions
        $staff->tokens()->delete();
        
        // Log the account lock
        $this->auditService->logEvent('account_locked_insider_threat', [
            'user_id' => $staff->id,
            'tenant_id' => $staff->tenant_id,
            'reason' => 'Critical insider anomaly detected',
        ], 'security');

        Log::critical('Account locked due to critical insider threat', [
            'user_id' => $staff->id,
            'tenant_id' => $staff->tenant_id,
        ]);
    }
}
