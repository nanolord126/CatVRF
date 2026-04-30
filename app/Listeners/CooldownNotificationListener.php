<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Security\CooldownStarted;
use App\Notifications\CooldownActivatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

/**
 * Cooldown Notification Listener
 * 
 * Sends notifications when a cooldown period is started.
 * Notifies the tenant owner and the affected user.
 */
final class CooldownNotificationListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(CooldownStarted $event): void
    {
        $cooldown = $event->cooldown;
        $user = $event->triggeredBy;

        // Notify the affected user
        if ($user !== null) {
            Notification::send($user, new CooldownActivatedNotification($cooldown));
        }

        // Notify tenant owner if different from user
        if ($cooldown->tenant_id !== null && $cooldown->user_id !== null) {
            $tenantOwner = $this->getTenantOwner($cooldown->tenant_id);
            
            if ($tenantOwner !== null && $tenantOwner->id !== $user?->id) {
                Notification::send($tenantOwner, new CooldownActivatedNotification($cooldown));
            }
        }
    }

    /**
     * Get tenant owner
     */
    private function getTenantOwner(int $tenantId): ?\App\Models\User
    {
        return \App\Models\TenantUser::where('tenant_id', $tenantId)
            ->where('role', 'owner')
            ->first()
            ?->user;
    }
}
