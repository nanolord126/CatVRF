<?php declare(strict_types=1);

namespace App\Services\Tenancy;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\DB;

/**
 * Tenant Quota Persistence Service
 *
 * Production 2026 CANON - Quota Usage Persistence
 *
 * Periodically flushes quota usage from Redis to ClickHouse/PostgreSQL
 * for long-term analytics, billing, and historical reporting.
 *
 * @author CatVRF Team
 * @version 2026.04.17
 */
final readonly class TenantQuotaPersistenceService
{
    private const QUOTA_PREFIX = 'tenant:quota:';
    private const BATCH_SIZE = 1000;

    public function __construct(
        private readonly RedisFactory $redis,
        private readonly DatabaseManager $db,
        private readonly LogManager $logger,
    ) {}

    /**
     * Flush all quota usage to database
     * Should be called by scheduled job (e.g., every 5 minutes)
     */
    public function flushToDatabase(): int
    {
        $flushed = 0;
        $patterns = [
            'ai_tokens:*',
            'redis_ops:*',
            'db_queries:*',
            'storage_bytes:*',
        ];

        foreach ($patterns as $pattern) {
            $keys = $this->redis->connection()->keys(self::QUOTA_PREFIX . $pattern);
            
            foreach ($keys as $key) {
                if (str_contains($key, 'custom:')) {
                    continue; // Skip custom quota keys
                }

                $flushed += $this->flushKeyToDatabase($key);
            }
        }

        $this->logger->info('Quota usage flushed to database', [
            'records_flushed' => $flushed,
        ]);

        return $flushed;
    }

    /**
     * Flush a single quota key to database
     */
    private function flushKeyToDatabase(string $key): int
    {
        $value = (int) $this->redis->connection()->get($key) ?: 0;

        if ($value === 0) {
            return 0;
        }

        // Parse key: tenant:quota:ai_tokens:123
        $parts = explode(':', $key);
        if (count($parts) !== 4) {
            return 0;
        }

        [, , $resourceType, $tenantId] = $parts;

        try {
            DB::table('tenant_quota_usage')->insert([
                'tenant_id' => (int) $tenantId,
                'resource_type' => $resourceType,
                'usage' => $value,
                'recorded_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Reset Redis counter after successful flush
            $this->redis->connection()->del($key);

            return 1;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to flush quota usage to database', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Get historical quota usage for a tenant
     */
    public function getHistoricalUsage(int $tenantId, string $resourceType, int $days = 30): array
    {
        return DB::table('tenant_quota_usage')
            ->where('tenant_id', $tenantId)
            ->where('resource_type', $resourceType)
            ->where('recorded_at', '>=', now()->subDays($days))
            ->orderBy('recorded_at')
            ->get()
            ->toArray();
    }

    /**
     * Get aggregate usage across all tenants for billing
     */
    public function getAggregateUsage(string $resourceType, string $period = 'daily'): array
    {
        $groupBy = match($period) {
            'hourly' => DB::raw('DATE_FORMAT(recorded_at, "%Y-%m-%d %H:00:00")'),
            'daily' => DB::raw('DATE(recorded_at)'),
            'monthly' => DB::raw('DATE_FORMAT(recorded_at, "%Y-%m")'),
            default => DB::raw('DATE(recorded_at)'),
        };

        return DB::table('tenant_quota_usage')
            ->select([
                'tenant_id',
                $groupBy . ' as period',
                DB::raw('SUM(usage) as total_usage'),
                DB::raw('COUNT(*) as record_count'),
            ])
            ->where('resource_type', $resourceType)
            ->where('recorded_at', '>=', now()->subDays(30))
            ->groupBy('tenant_id', 'period')
            ->orderBy('period', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Create quota usage table if not exists
     */
    public function ensureTableExists(): void
    {
        $schema = DB::connection()->getSchemaBuilder();

        if (!$schema->hasTable('tenant_quota_usage')) {
            $schema->create('tenant_quota_usage', function ($table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('resource_type', 50)->index();
                $table->bigInteger('usage')->default(0);
                $table->timestamp('recorded_at')->index();
                $table->timestamps();

                $table->index(['tenant_id', 'resource_type', 'recorded_at']);
            });

            $this->logger->info('tenant_quota_usage table created');
        }
    }
}
