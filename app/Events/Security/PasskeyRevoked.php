<?php

declare(strict_types=1);

namespace App\Events\Security;

use App\Models\User;
use App\Models\WebauthnCredential;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PasskeyRevoked
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly WebauthnCredential $credential,
        public readonly string $reason
    ) {}
}
