<?php

declare(strict_types=1);

namespace App\Listeners\Security;

use App\Events\Security\SplitKeyGenerated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Log Split Key Generated
 *
 * Logs split key generation for audit purposes.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final readonly class LogSplitKeyGenerated implements ShouldQueue
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(SplitKeyGenerated $event): void
    {
        Log::channel('audit')->info('Split key generated', [
            'split_key_id' => $event->splitKey->id,
            'user_id' => $event->user->id,
            'tenant_id' => $event->splitKey->tenant_id,
            'ip_address' => $event->ipAddress,
            'correlation_id' => $event->correlationId,
            'expires_at' => $event->splitKey->expires_at?->toIso8601String(),
        ]);
    }
}
