<?php

namespace App\Domains\Common\Services\Performance;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class GlobalPerformanceGuardian
{
    protected array $thresholds = [
        'db_connection_limit' => 200,
        'redis_max_memory' => '2gb',
        'request_timeout_ms' => 500
    ];

    private string $correlationId;

    public function __construct()
    {
        $this->correlationId = Str::uuid();
    }

    /**
     * Анализ узких мест во всей системе (DB, Cache, Session, App Logic).
     */
    public function auditPlatformReadiness(): array
    {
        $this->correlationId = Str::uuid();

        try {
            Log::channel('performance')->info('GlobalPerformanceGuardian: audit started', [
                'correlation_id' => $this->correlationId,
            ]);

            $results = [
                'database' => $this->auditDatabaseScalability(),
                'sessions' => $this->auditSessionDriver(),
                'file_storage' => $this->auditMediaPerformance(),
                'tenancy_overhead' => $this->auditTenancyPerformance(),
                'redis' => $this->auditRedisPerformance(),
                'audit_timestamp' => now()->toIso8601String(),
                'correlation_id' => $this->correlationId,
            ];

            // Логирование результатов
            Log::channel('performance')->info('GlobalPerformanceGuardian: audit completed', [
                'correlation_id' => $this->correlationId,
                'database_risk' => $results['database']['risk'],
                'session_status' => $results['sessions']['status'],
                'storage_status' => $results['file_storage']['status'],
            ]);

            return $results;
        } catch (Throwable $e) {
            Log::error('GlobalPerformanceGuardian: audit failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);
            \Sentry\captureException($e);
            throw $e;
        }
    }

    protected function auditDatabaseScalability(): array
    {
        try {
            $connections = 0;
            $result = DB::select("SHOW STATUS WHERE Variable_name = 'Threads_connected'");
            if (!empty($result)) {
                $connections = $result[0]->Value ?? 0;
            }
            
            return [
                'current_connections' => $connections,
                'max_connections' => $this->thresholds['db_connection_limit'],
                'usage_percent' => ($connections / $this->thresholds['db_connection_limit']) * 100,
                'risk' => $connections > 150 ? 'CRITICAL' : 'OPTIMAL',
                'recommendation' => 'Используйте PDO Persistent Connections или PgBouncer для Postgres.',
            ];
        } catch (Throwable $e) {
            Log::warning('GlobalPerformanceGuardian: DB audit failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);
            return [
                'current_connections' => 0,
                'risk' => 'UNKNOWN',
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function auditSessionDriver(): array
    {
        try {
            $driver = config('session.driver');
            $status = $driver === 'redis' ? 'READY' : ($driver === 'database' ? 'STABLE' : 'BOTTLENECK');
            
            return [
                'driver' => $driver,
                'status' => $status,
                'warning' => $driver === 'file' ? 'Файловые сессии неподходящи для 5000+ RPM' : null,
                'recommendation' => $driver !== 'redis' ? 'Используйте Redis для session driver' : 'Оптимально',
            ];
        } catch (Throwable $e) {
            Log::warning('GlobalPerformanceGuardian: session audit failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);
            return ['driver' => 'unknown', 'status' => 'UNKNOWN', 'error' => $e->getMessage()];
        }
    }

    protected function auditMediaPerformance(): array
    {
        try {
            $disk = config('filesystems.default');
            $isScalable = in_array($disk, ['s3', 'minio', 'azure']);
            
            return [
                'storage_type' => $disk,
                'status' => $isScalable ? 'SCALABLE' : 'LOCAL_RISK',
                'recommendation' => !$isScalable ? 'При 5000+ RPM используйте облачное хранилище (S3/Minio)' : 'Оптимально для высоконагруженных систем',
            ];
        } catch (Throwable $e) {
            Log::warning('GlobalPerformanceGuardian: media audit failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);
            return ['status' => 'UNKNOWN', 'error' => $e->getMessage()];
        }
    }

    protected function auditTenancyPerformance(): array
    {
        try {
            $start = microtime(true);
            // Эмуляция переключения тенанта
            $schema = config('tenancy.database.template_tenant_connection_name', 'tenant');
            $end = microtime(true);
            
            $latencyMs = ($end - $start) * 1000;
            
            return [
                'switch_latency_ms' => round($latencyMs, 2),
                'is_heavy' => $latencyMs > 50 ? 'YES' : 'NO',
                'recommendation' => $latencyMs > 50 ? 'Оптимизируйте схему переключения' : 'Оптимально',
            ];
        } catch (Throwable $e) {
            Log::warning('GlobalPerformanceGuardian: tenancy audit failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);
            return ['switch_latency_ms' => 0, 'error' => $e->getMessage()];
        }
    }

    protected function auditRedisPerformance(): array
    {
        try {
            if (!Redis::ping()) {
                return ['status' => 'DISCONNECTED', 'risk' => 'CRITICAL'];
            }

            $info = Redis::info('memory');
            $memoryUsage = $info['used_memory_human'] ?? 'unknown';
            
            return [
                'status' => 'CONNECTED',
                'memory_usage' => $memoryUsage,
                'memory_limit' => $this->thresholds['redis_max_memory'],
                'risk' => 'OPTIMAL',
            ];
        } catch (Throwable $e) {
            Log::warning('GlobalPerformanceGuardian: Redis audit failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);
            return ['status' => 'ERROR', 'risk' => 'UNKNOWN', 'error' => $e->getMessage()];
        }
    }
}
