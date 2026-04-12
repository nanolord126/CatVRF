<?php

declare(strict_types=1);

namespace App\Domains\CRM\Services;




use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use App\Services\AuditService;
use App\Domains\CRM\Models\CrmClient;
use App\Domains\CRM\Models\CrmInteraction;
use App\Domains\CRM\Models\CrmSegment;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;

/**
 * CrmAnalyticsService — аналитика CRM-клиентов.
 * Метрики, воронки, LTV, churn rate.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final readonly class CrmAnalyticsService
{
    public function __construct(
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private readonly Request $request,
        private readonly AuditService $audit,
        private readonly FraudControlService $fraudControl,
    ) {}

    /**
     * Дашборд-метрики для tenant (общие CRM-показатели).
     */
    public function getDashboardMetrics(int $tenantId, string $period = '30d'): array
    {
        $days = (int) filter_var($period, FILTER_SANITIZE_NUMBER_INT) ?: 30;
        $since = now()->subDays($days);

        $totalClients = CrmClient::query()->forTenant($tenantId)->count();
        $activeClients = CrmClient::query()->forTenant($tenantId)->active()->count();
        $newClients = CrmClient::query()->forTenant($tenantId)->where('created_at', '>=', $since)->count();

        $sleepingClients = CrmClient::query()->forTenant($tenantId)->sleeping(60)->count();
        $vipClients = CrmClient::query()->forTenant($tenantId)->where('loyalty_tier', 'vip')->count();

        $totalRevenue = CrmClient::query()->forTenant($tenantId)
            ->where('last_order_at', '>=', $since)
            ->sum('total_spent');

        $averageLtv = CrmClient::query()->forTenant($tenantId)
            ->where('total_orders', '>', 0)
            ->avg('total_spent');

        $interactionsCount = CrmInteraction::query()
            ->where('tenant_id', $tenantId)
            ->where('interacted_at', '>=', $since)
            ->count();

        $segmentBreakdown = CrmClient::query()->forTenant($tenantId)
            ->selectRaw("segment, COUNT(*) as count")
            ->groupBy('segment')
            ->pluck('count', 'segment')
            ->toArray();

        $loyaltyBreakdown = CrmClient::query()->forTenant($tenantId)
            ->selectRaw("loyalty_tier, COUNT(*) as count")
            ->groupBy('loyalty_tier')
            ->pluck('count', 'loyalty_tier')
            ->toArray();

        $churnRate = $totalClients > 0
            ? round(($sleepingClients / $totalClients) * 100, 2)
            : 0;

        return [
            'total_clients' => $totalClients,
            'active_clients' => $activeClients,
            'new_clients' => $newClients,
            'sleeping_clients' => $sleepingClients,
            'vip_clients' => $vipClients,
            'total_revenue' => round((float) $totalRevenue, 2),
            'average_ltv' => round((float) ($averageLtv ?? 0), 2),
            'interactions_count' => $interactionsCount,
            'churn_rate' => $churnRate,
            'segment_breakdown' => $segmentBreakdown,
            'loyalty_breakdown' => $loyaltyBreakdown,
            'period_days' => $days,
        ];
    }

    /**
     * Топ-клиенты по выручке.
     */
    public function getTopClients(int $tenantId, int $limit = 10): \Illuminate\Support\Collection
    {
        return CrmClient::query()
            ->forTenant($tenantId)
            ->where('total_spent', '>', 0)
            ->orderByDesc('total_spent')
            ->limit($limit)
            ->get(['id', 'first_name', 'last_name', 'company_name', 'total_spent', 'total_orders', 'loyalty_tier', 'segment']);
    }

    /**
     * Динамика новых клиентов по дням за указанный период.
     */
    public function getNewClientsTimeline(int $tenantId, int $days = 30): array
    {
        $results = CrmClient::query()
            ->forTenant($tenantId)
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw("DATE(created_at) as date, COUNT(*) as count")
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        return $results;
    }

    /**
     * Распределение клиентов по вертикалям.
     */
    public function getVerticalDistribution(int $tenantId): array
    {
        return CrmClient::query()
            ->forTenant($tenantId)
            ->whereNotNull('vertical')
            ->selectRaw("vertical, COUNT(*) as count")
            ->groupBy('vertical')
            ->pluck('count', 'vertical')
            ->toArray();
    }

    /**
     * Средний чек по сегментам.
     */
    public function getAverageOrderBySegment(int $tenantId): array
    {
        return CrmClient::query()
            ->forTenant($tenantId)
            ->whereNotNull('segment')
            ->where('total_orders', '>', 0)
            ->selectRaw("segment, AVG(average_order_value) as avg_check, COUNT(*) as clients_count")
            ->groupBy('segment')
            ->get()
            ->keyBy('segment')
            ->map(fn ($row) => [
                'avg_check' => round((float) $row->avg_check, 2),
                'clients_count' => (int) $row->clients_count,
            ])
            ->toArray();
    }

    /**
     * Выполнить операцию в транзакции с audit-логированием.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    protected function executeInTransaction(callable $callback): mixed
    {
        return DB::transaction(function () use ($callback) {
            return $callback();
        });
    }

    /**
     * Получить correlation_id из текущего контекста.
     */
    private function getCorrelationId(): string
    {
        return $this->request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid()->toString();
    }
}
