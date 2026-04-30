<?php

declare(strict_types=1);

namespace App\Events\Security;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Staff Invited Event
 * 
 * Dispatched when a new staff member is invited to a tenant.
 * Triggers a cooldown period for financial operations.
 */
final class StaffInvited
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly User $invitedBy,
        public readonly int $tenantId,
        public readonly string $role,
        public readonly ?string $ipAddress = null
    ) {}
}
