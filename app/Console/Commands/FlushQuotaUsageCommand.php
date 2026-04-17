<?php declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Tenancy\TenantQuotaPersistenceService;
use Illuminate\Console\Command;

/**
 * Flush Quota Usage Command
 *
 * Production 2026 CANON - Scheduled Task
 *
 * Flushes quota usage from Redis to PostgreSQL for persistence.
 * Should be scheduled to run every 5-10 minutes.
 *
 * @author CatVRF Team
 * @version 2026.04.17
 */
final class FlushQuotaUsageCommand extends Command
{
    protected $signature = 'quota:flush';

    protected $description = 'Flush tenant quota usage from Redis to database';

    public function handle(TenantQuotaPersistenceService $persistenceService): int
    {
        $this->info('Starting quota usage flush...');

        $persistenceService->ensureTableExists();
        $flushed = $persistenceService->flushToDatabase();

        $this->info("Flushed {$flushed} quota usage records to database.");

        return self::SUCCESS;
    }
}
