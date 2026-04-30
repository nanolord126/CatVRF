<?php

declare(strict_types=1);

namespace App\Events\Security;

use App\Models\SplitKey;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Split Key Invalidated Event
 *
 * Dispatched when a split key is invalidated due to risk, fraud, or security concerns.
 * Triggers cooldown and notifications.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final class SplitKeyInvalidated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly SplitKey $splitKey,
        public readonly User $user,
        public readonly string $reason,
        public readonly string $riskLevel,
        public readonly ?string $source = null,
        public readonly ?string $ipAddress = null,
        public readonly ?string $correlationId = null
    ) {}
}
