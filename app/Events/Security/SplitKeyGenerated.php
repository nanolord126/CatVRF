<?php

declare(strict_types=1);

namespace App\Events\Security;

use App\Models\SplitKey;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Split Key Generated Event
 *
 * Dispatched when a split key is generated after successful authentication.
 * Used for audit logging and notifications.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final class SplitKeyGenerated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly SplitKey $splitKey,
        public readonly User $user,
        public readonly ?string $ipAddress = null,
        public readonly ?string $correlationId = null
    ) {}
}
