<?php

declare(strict_types=1);

namespace App\Events\Security;

use App\Models\CooldownPeriod;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Cooldown Started Event
 * 
 * Dispatched when a cooldown period is triggered.
 * Used for notifications and audit logging.
 */
final class CooldownStarted
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly CooldownPeriod $cooldown,
        public readonly ?User $triggeredBy = null
    ) {}
}
