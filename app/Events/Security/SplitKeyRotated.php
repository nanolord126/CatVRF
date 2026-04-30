<?php

declare(strict_types=1);

namespace App\Events\Security;

use App\Models\SplitKey;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Split Key Rotated Event
 *
 * Dispatched when a split key is rotated after inactivity or security event.
 * Used for audit logging and tracking key lifecycle.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final class SplitKeyRotated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly SplitKey $oldSplitKey,
        public readonly SplitKey $newSplitKey,
        public readonly User $user,
        public readonly string $reason,
        public readonly ?string $ipAddress = null,
        public readonly ?string $correlationId = null
    ) {}
}
