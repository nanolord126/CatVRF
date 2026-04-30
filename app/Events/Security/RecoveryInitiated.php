<?php

declare(strict_types=1);

namespace App\Events\Security;

use App\Models\AccountRecoveryLog;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class RecoveryInitiated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly AccountRecoveryLog $recoveryLog
    ) {}
}
