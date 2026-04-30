<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\AuditService;
use Modules\CatCRM\Domain\Entities\Customer;
use Modules\CatCRM\Domain\Entities\B2BLead;
use Modules\CatCRM\Domain\Entities\Deal;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\DatabaseManager;

final readonly class CRMAnalyticsService
{
    use WithAuditLogging;

    private const CACHE_TTL = 3600;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly CacheManager $cache,
        private readonly AuditService $auditService,
    ) {}

    public function getCRMStats(int $tenantId, \DateTime $startDate, \DateTime $endDate): array
    {
        $cacheKey = "crm_stats_{$tenantId}_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";
        
        return $this->cache->tags(["crm:{$tenantId}"])->remember($cacheKey, self::CACHE_TTL, function () use ($tenantId, $startDate, $endDate) {
            return [
                'b2c' => $this->getB2CStats($tenantId, $startDate, $endDate),
                'b2b' => $this->getB2BStats($tenantId, $startDate, $endDate),
                'conversion' => $this->getConversionStats($tenantId, $startDate, $endDate),
                'revenue' => $this->getRevenueStats($tenantId, $startDate, $endDate),
            ];
        });
    }

    public function getB2CStats(int $tenantId, \DateTime $startDate, \DateTime $endDate): array
    {
        $customers = Customer::where('tenant_id', $tenantId)
            ->where('type', 'individual')
            ->whereBetween('created_at', [$startDate, $endDate]);

        $deals = Deal::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate]);

        return [
            'new_customers' => $customers->count(),
            'active_customers' => Customer::where('tenant_id', $tenantId)
                ->where('type', 'individual')
                ->where('last_interaction_at', '>=', $startDate)
                ->count(),
            'total_deals' => $deals->count(),
            'won_deals' => $deals->where('status', 'won')->count(),
            'win_rate' => $deals->count() > 0 
                ? round(($deals->where('status', 'won')->count() / $deals->count()) * 100, 2) 
                : 0,
            'average_deal_value' => $deals->where('status', 'won')->avg('value') ?? 0,
            'total_ltv' => Customer::where('tenant_id', $tenantId)
                ->where('type', 'individual')
                ->sum('total_spent'),
        ];
    }

    public function getB2BStats(int $tenantId, \DateTime $startDate, \DateTime $endDate): array
    {
        $leads = B2BLead::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate]);

        return [
            'new_leads' => $leads->count(),
            'converted_leads' => $leads->where('status', 'converted')->count(),
            'won_leads' => $leads->where('status', 'won')->count(),
            'conversion_rate' => $leads->count() > 0 
                ? round(($leads->where('status', 'converted')->count() / $leads->count()) * 100, 2) 
                : 0,
            'by_vertical' => $leads->groupBy('vertical_id')->map(fn($g) => $g->count())->toArray(),
            'by_source' => $leads->groupBy('source')->map(fn($g) => $g->count())->toArray(),
            'estimated_pipeline_value' => $leads->whereIn('status', ['qualified', 'proposal', 'negotiation'])
                ->sum(fn($l) => $l->estimateValue()),
        ];
    }

    public function getConversionStats(int $tenantId, \DateTime $startDate, \DateTime $endDate): array
    {
        return [
            'lead_to_deal' => $this->calculateLeadToDealConversion($tenantId, $startDate, $endDate),
            'deal_to_win' => $this->calculateDealToWinConversion($tenantId, $startDate, $endDate),
            'customer_retention' => $this->calculateCustomerRetention($tenantId, $startDate, $endDate),
            'by_pipeline' => $this->getPipelineConversionRates($tenantId, $startDate, $endDate),
        ];
    }

    public function getRevenueStats(int $tenantId, \DateTime $startDate, \DateTime $endDate): array
    {
        $b2cRevenue = Deal::where('tenant_id', $tenantId)
            ->where('status', 'won')
            ->whereBetween('actual_close_date', [$startDate, $endDate])
            ->sum('value');

        $b2bRevenue = \Modules\CatCRM\Domain\Entities\B2BDeal::where('tenant_id', $tenantId)
            ->where('status', 'won')
            ->whereBetween('actual_close_date', [$startDate, $endDate])
            ->sum('value');

        return [
            'total' => $b2cRevenue + $b2bRevenue,
            'b2c' => $b2cRevenue,
            'b2b' => $b2bRevenue,
            'b2c_percentage' => ($b2cRevenue + $b2bRevenue) > 0 
                ? round(($b2cRevenue / ($b2cRevenue + $b2bRevenue)) * 100, 2) 
                : 0,
            'b2b_percentage' => ($b2cRevenue + $b2bRevenue) > 0 
                ? round(($b2bRevenue / ($b2cRevenue + $b2bRevenue)) * 100, 2) 
                : 0,
            'monthly_trend' => $this->getMonthlyRevenueTrend($tenantId, $startDate, $endDate),
        ];
    }

    public function getFunnelData(int $tenantId, \DateTime $startDate, \DateTime $endDate): array
    {
        return [
            'leads' => B2BLead::where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'contacted' => B2BLead::where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'contacted')
                ->count(),
            'qualified' => B2BLead::where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'qualified')
                ->count(),
            'proposal' => B2BLead::where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'proposal')
                ->count(),
            'negotiation' => B2BLead::where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'negotiation')
                ->count(),
            'won' => B2BLead::where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'won')
                ->count(),
            'lost' => B2BLead::where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'lost')
                ->count(),
        ];
    }

    public function exportToClickHouse(int $tenantId, \DateTime $startDate, \DateTime $endDate): bool
    {
        $stats = $this->getCRMStats($tenantId, $startDate, $endDate);

        // Insert into ClickHouse for analytics
        $this->db->connection('clickhouse')->table('crm_analytics')->insert([
            'tenant_id' => $tenantId,
            'date' => now()->toDateString(),
            'b2c_new_customers' => $stats['b2c']['new_customers'],
            'b2b_new_leads' => $stats['b2b']['new_leads'],
            'b2c_revenue' => $stats['revenue']['b2c'],
            'b2b_revenue' => $stats['revenue']['b2b'],
            'total_revenue' => $stats['revenue']['total'],
            'conversion_rate' => $stats['conversion']['lead_to_deal'],
            'created_at' => now(),
        ]);

        return true;
    }

    private function calculateLeadToDealConversion(int $tenantId, \DateTime $startDate, \DateTime $endDate): float
    {
        $leads = B2BLead::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $converted = B2BLead::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'converted')
            ->count();

        return $leads > 0 ? round(($converted / $leads) * 100, 2) : 0;
    }

    private function calculateDealToWinConversion(int $tenantId, \DateTime $startDate, \DateTime $endDate): float
    {
        $deals = Deal::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $won = Deal::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'won')
            ->count();

        return $deals > 0 ? round(($won / $deals) * 100, 2) : 0;
    }

    private function calculateCustomerRetention(int $tenantId, \DateTime $startDate, \DateTime $endDate): float
    {
        $periodStart = clone $startDate;
        $periodStart->modify('-1 month');
        $activeAtStart = Customer::where('tenant_id', $tenantId)
            ->where('last_interaction_at', '<', $startDate)
            ->where('last_interaction_at', '>=', $periodStart)
            ->count();

        $stillActive = Customer::where('tenant_id', $tenantId)
            ->where('last_interaction_at', '>=', $startDate)
            ->count();

        return $activeAtStart > 0 ? round(($stillActive / $activeAtStart) * 100, 2) : 0;
    }

    private function getPipelineConversionRates(int $tenantId, \DateTime $startDate, \DateTime $endDate): array
    {
        $pipelines = \Modules\CatCRM\Domain\Entities\Pipeline::where('tenant_id', $tenantId)->get();

        return $pipelines->mapWithKeys(function ($pipeline) use ($tenantId, $startDate, $endDate) {
            $total = Deal::where('tenant_id', $tenantId)
                ->where('pipeline_id', $pipeline->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            $won = Deal::where('tenant_id', $tenantId)
                ->where('pipeline_id', $pipeline->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'won')
                ->count();

            return [$pipeline->name => $total > 0 ? round(($won / $total) * 100, 2) : 0];
        })->toArray();
    }

    private function getMonthlyRevenueTrend(int $tenantId, \DateTime $startDate, \DateTime $endDate): array
    {
        $months = [];
        $current = clone $startDate;

        while ($current <= $endDate) {
            $monthStart = clone $current;
            $monthStart->modify('first day of this month 00:00:00');
            $monthEnd = clone $current;
            $monthEnd->modify('last day of this month 23:59:59');

            $revenue = Deal::where('tenant_id', $tenantId)
                ->where('status', 'won')
                ->whereBetween('actual_close_date', [$monthStart, $monthEnd])
                ->sum('value');

            $months[] = [
                'month' => $current->format('Y-m'),
                'revenue' => $revenue,
            ];

            $current->modify('+1 month');
        }

        return $months;
    }

    private function invalidateMetricCache(string $vertical, string $metricType, ?string $tenantId): void
    {
        $this->cache->tags(["crm:{$tenantId}"])->flush();
    }

    public function recordMetric(
        string $vertical,
        string $metricType,
        array $data,
        ?string $tenantId = null
    ): void {
        // Fraud check before recording metric
        // TODO: Integrate with FraudDetectionService when available

        $metricData = [
            'vertical' => $vertical,
            'metric_type' => $metricType,
            'tenant_id' => $tenantId,
            'data' => $data,
            'recorded_at' => now(),
        ];

        $this->db->table('analytics_metrics')->insert($metricData);

        $this->invalidateMetricCache($vertical, $metricType, $tenantId);

        $this->logAction(
            action: 'metric_recorded',
            entityType: 'AnalyticsMetric',
            entityId: null,
            context: [
                'vertical' => $vertical,
                'metric_type' => $metricType,
            ],
            userId: null,
            tenantId: $tenantId
        );
    }
}
