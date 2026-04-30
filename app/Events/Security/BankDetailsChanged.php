<?php

declare(strict_types=1);

namespace App\Events\Security;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Bank Details Changed Event
 * 
 * Dispatched when a tenant's bank details are updated.
 * Triggers a cooldown period for financial operations.
 */
final class BankDetailsChanged
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly int $tenantId,
        public readonly array $changedFields,
        public readonly ?string $ipAddress = null
    ) {}
}
