<?php

declare(strict_types=1);

namespace App\Listeners\Security;

use App\Enums\CooldownActionType;
use App\Events\Security\StaffInvited;
use App\Services\Security\CooldownService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Staff Invited Cooldown Listener
 * 
 * Triggers a cooldown period when a new staff member is invited.
 */
final class StaffInvitedCooldownListener implements ShouldQueue
{
    public function __construct(
        private readonly CooldownService $cooldownService
    ) {}

    public function handle(StaffInvited $event): void
    {
        $this->cooldownService->startCooldown(
            user: $event->invitedBy,
            actionType: CooldownActionType::STAFF_INVITE,
            tenantId: $event->tenantId,
            reason: "Staff invited with role {$event->role} - cooldown activated for security",
            metadata: [
                'role' => $event->role,
                'ip_address' => $event->ipAddress,
            ]
        );
    }
}
