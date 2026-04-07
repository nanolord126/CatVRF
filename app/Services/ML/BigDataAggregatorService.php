<?php declare(strict_types=1);

namespace App\Services\ML;


use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Collection;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;


/**
 * BigDataAggregatorService — запись обезличенных событий в ClickHouse
 * и агрегация метрик для дашбордов и ML-моделей.
 *
 * Правило: в ClickHouse записываются ТОЛЬКО анонимизированные данные.
 * Raw user_id сюда никогда не попадает.
 */
final readonly class BigDataAggregatorService
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}

    public function insertAnonymizedEvent(array $anonymizedEvent): void
    {
        // В реальном окружении — ClickHouse HTTP-insert через configured driver.
        // В тестах/dev — логируем структуру события.
        try {
            $connection = $this->config->get('database.clickhouse.connection', null);

            if ($connection !== null) {
                $this->db->connection('clickhouse')->table('anonymized_behavior')->insert($anonymizedEvent);
            } else {
                $this->logger->channel('audit')->debug('ClickHouse: anonymized_behavior insert (dev-mode)', [
                    'fields'         => array_keys($anonymizedEvent),
                    'correlation_id' => $anonymizedEvent['correlation_id'] ?? '',
                ]);
            }
        } catch (\Throwable $e) {
            // ClickHouse недоступен — не ломаем основной поток
            $this->logger->channel('audit')->warning('BigDataAggregatorService: ClickHouse write failed', [
                'error'          => $e->getMessage(),
                'correlation_id' => $anonymizedEvent['correlation_id'] ?? '',
            ]);
        }
    }

    public function insertMarketingEvent(array $anonymizedEvent): void
    {
        try {
            $connection = $this->config->get('database.clickhouse.connection', null);

            if ($connection !== null) {
                $this->db->connection('clickhouse')->table('marketing_events')->insert($anonymizedEvent);
            }
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->warning('BigDataAggregatorService: marketing_events write failed', [
                'error'          => $e->getMessage(),
                'correlation_id' => $anonymizedEvent['correlation_id'] ?? '',
            ]);
        }
    }

    public function insertSecurityEvent(array $event): void
    {
        try {
            $connection = $this->config->get('database.clickhouse.connection', null);

            if ($connection !== null) {
                $this->db->connection('clickhouse')->table('security_events')->insert($event);
            }
        } catch (\Throwable $e) {
            $this->logger->channel('security')->warning('BigDataAggregatorService: security_events write failed', [
                'error'          => $e->getMessage(),
                'correlation_id' => $event['correlation_id'] ?? '',
            ]);
        }
    }

    public function insertAuditLog(array $log): void
    {
        try {
            $connection = $this->config->get('database.clickhouse.connection', null);

            if ($connection !== null) {
                $this->db->connection('clickhouse')->table('audit_logs_archive')->insert($log);
            }
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->warning('BigDataAggregatorService: audit_logs_archive write failed', [
                'error'          => $e->getMessage(),
                'correlation_id' => $log['correlation_id'] ?? '',
            ]);
        }
    }

    // ─── Aggregation helpers (для метрик/дашбордов) ──────────────────────────

    public function getGMV(int $tenantId, string $period = '30d'): float
    {
        $days = $this->parsePeriodDays($period);

        return (float) $this->db->table('orders')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->subDays($days))
            ->whereIn('status', ['completed', 'delivered'])
            ->sum('total_amount');
    }

    public function getOrdersCount(int $tenantId, string $period = '30d'): int
    {
        $days = $this->parsePeriodDays($period);

        return (int) $this->db->table('orders')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->subDays($days))
            ->count();
    }

    private function parsePeriodDays(string $period): int
    {
        // '30d' → 30, '7d' → 7, '90d' → 90
        return (int) rtrim($period, 'd');
    }
}
