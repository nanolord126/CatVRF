<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domains\Audit\Services\AuditService;
use Illuminate\Support\Facades\Log;

/**
 * AuditPruneCommand — Artisan command for manual audit log pruning.
 * Can be run manually or scheduled via cron.
 */
final class AuditPruneCommand extends Command
{
    protected $signature = 'audit:prune 
                            {--force : Force pruning without confirmation}
                            {--days= : Override retention period in days}';

    protected $description = 'Prune old audit logs based on retention policy';

    public function __construct(
        private readonly AuditService $auditService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting audit log pruning...');

        $retentionMonths = $this->option('days')
            ? (int) $this->option('days') / 30
            : $this->auditService->getRetentionMonths();

        $this->info("Retention period: {$retentionMonths} months");

        if (! $this->option('force')) {
            if (! $this->confirm('Are you sure you want to prune old audit logs? This action cannot be undone.')) {
                $this->warn('Pruning cancelled.');

                return self::SUCCESS;
            }
        }

        $startTime = now();

        try {
            $deletedCount = $this->auditService->pruneOldLogs();

            $duration = now()->diffInSeconds($startTime);

            $this->info("Audit log pruning completed successfully.");
            $this->info("Deleted records: {$deletedCount}");
            $this->info("Duration: {$duration} seconds");

            Log::channel('audit')->info('Audit log pruning completed via command', [
                'deleted_count' => $deletedCount,
                'duration_seconds' => $duration,
            ]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Audit log pruning failed: {$e->getMessage()}");

            Log::channel('security')->error('Audit log pruning failed via command', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }
}
