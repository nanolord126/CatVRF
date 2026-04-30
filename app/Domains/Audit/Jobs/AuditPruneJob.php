<?php

declare(strict_types=1);

namespace App\Domains\Audit\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Domains\Audit\Services\AuditService;

/**
 * AuditPruneJob — Scheduled job for pruning old audit logs.
 * Runs automatically based on retention policy configuration.
 */
final class AuditPruneJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 3600; // 1 hour for large datasets

    public string $queue = 'audit-maintenance';

    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function handle(): void
    {
        $startTime = now();

        Log::channel('audit')->info('Starting audit log pruning', [
            'start_time' => $startTime->toIso8601String(),
        ]);

        try {
            $deletedCount = $this->auditService->pruneOldLogs();

            $duration = now()->diffInSeconds($startTime);

            Log::channel('audit')->info('Audit log pruning completed', [
                'deleted_count' => $deletedCount,
                'duration_seconds' => $duration,
                'end_time' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            Log::channel('security')->error('Audit log pruning failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->fail($e);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('security')->error('[AuditPruneJob] Failed permanently', [
            'error' => $exception->getMessage(),
        ]);
    }
}
