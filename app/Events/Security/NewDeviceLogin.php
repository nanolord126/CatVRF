<?php

declare(strict_types=1);

namespace App\Events\Security;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * New Device Login Event
 * 
 * Dispatched when a user logs in from a new device.
 * Triggers a cooldown period for financial operations.
 */
final class NewDeviceLogin
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly UserDevice $device,
        public readonly ?string $ipAddress = null
    ) {}
}
