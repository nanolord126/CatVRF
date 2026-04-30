<?php

declare(strict_types=1);

namespace App\Listeners\Security;

use Carbon\CarbonImmutable;

use App\Events\Security\BruteForceDetected;
use App\Models\User;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Security\BruteForceCriticalAlert;
use App\Notifications\Security\BruteForceDetectedNotification;

final class SendBruteForceAlert
{
    public function handle(BruteForceDetected $event): void
    {
        $this->log->channel('security')->warning('Brute force detected', [
            'type' => $event->type,
            'user_id' => $event->user?->id,
            'email' => $event->email,
            'ip_address' => $event->ipAddress,
            'metadata' => $event->metadata,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ]);

        // Send notification to user if exists
        if ($event->user) {
            try {
                $event->user->notify(new BruteForceDetectedNotification(
                    type: $event->type,
                    ipAddress: $event->ipAddress,
                    timestamp: CarbonImmutable::now(),
                ));
            } catch (\Exception $e) {
                $this->log->error('Failed to send brute force notification to user', [
                    'user_id' => $event->user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Send alert to super-admins for critical threats
        if ($this->isCriticalThreat($event)) {
            $this->notifySuperAdmins($event);
        }
    }

    private function isCriticalThreat(BruteForceDetected $event): bool
    {
        $criticalTypes = ['account_lockout', 'fraud_ml', 'velocity'];

        return in_array($event->type, $criticalTypes, true);
    }

    private function notifySuperAdmins(BruteForceDetected $event): void
    {
        $superAdmins = User::platformAdmins()->active()->get();

        foreach ($superAdmins as $admin) {
            try {
                $admin->notify(new BruteForceCriticalAlert(
                    type: $event->type,
                    ipAddress: $event->ipAddress,
                    email: $event->email,
                    metadata: $event->metadata,
                ));
            } catch (\Exception $e) {
                $this->log->error('Failed to send critical brute force alert to admin', [
                    'admin_id' => $admin->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
