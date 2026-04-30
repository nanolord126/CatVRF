<?php

declare(strict_types=1);

namespace App\Events\Security;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Two-Factor Authentication Changed Event
 * 
 * Dispatched when a user enables, disables, or changes 2FA settings.
 * Triggers a cooldown period for financial operations.
 */
final class TwoFactorChanged
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $action, // 'enabled', 'disabled', 'changed'
        public readonly ?string $ipAddress = null,
        public readonly ?string $userAgent = null
    ) {}
}
