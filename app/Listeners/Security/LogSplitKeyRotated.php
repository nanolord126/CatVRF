<?php

declare(strict_types=1);

namespace App\Listeners\Security;

use App\Events\Security\SplitKeyRotated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Log Split Key Rotated
 *
 * Logs split key rotation for audit purposes.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final readonly class LogSplitKeyRotated implements ShouldQueue
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(SplitKeyRotated $event): void
    {
        Log::channel('audit')->info('Split key rotated', [
            'old_split_key_id' => $event->oldSplitKey->id,
            'new_split_key_id' => $event->newSplitKey->id,
            'user_id' => $event->user->id,
            'tenant_id' => $event->newSplitKey->tenant_id,
            'reason' => $event->reason,
            'ip_address' => $event->ipAddress,
            'correlation_id' => $event->correlationId,
        ]);
    }
}
