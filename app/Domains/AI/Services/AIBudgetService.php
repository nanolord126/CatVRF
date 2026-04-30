<?php

declare(strict_types=1);

namespace App\Domains\AI\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\DatabaseManager;
use Carbon\CarbonInterface;
use RuntimeException;
use Psr\Log\LoggerInterface;

/**
 * AI Budget Management Service
 *
 * Manages budget limits for AI operations per tenant.
 * Prevents overspending and enables cost control.
 *
 * Features:
 * - Per-tenant monthly budgets
 * - Real-time spend tracking
 * - Alert thresholds (80%, 90%, 100%)
 * - Hard limits (stop operations when exceeded)
 *
 * Strictly follows CatVRF rules:
 * - No facades, uses dependency injection
 * - Database operations through proper connection
 * - Cache with proper TTL and invalidation
 * - Comprehensive logging for observability
 */
final readonly class AIBudgetService
{
    private const string BUDGET_PREFIX = 'ai_budget:';
    private const string SPEND_PREFIX = 'ai_spend:';
    private const int DEFAULT_TTL = 2592000;
    private const float DEFAULT_MONTHLY_BUDGET = 1000.00;
    private const float ALERT_THRESHOLD_WARNING = 80.0;
    private const float ALERT_THRESHOLD_CRITICAL = 90.0;

    /**
     * Check if tenant has budget for AI operation
     *
     * @param int $tenantId Tenant ID
     * @param float $estimatedCost Estimated cost in currency
     * @return bool True if budget available
     * @throws RuntimeException If budget exceeded
     */
    public function checkBudget(int $tenantId, float $estimatedCost): bool
    {
        $budget = $this->getTenantBudget($tenantId);
        $spent = $this->getTenantSpend($tenantId);
        $remaining = $budget - $spent;
        $usagePercentage = $budget > 0 ? ($spent / $budget) * 100 : 0;

        if ($remaining < $estimatedCost) {
            $this->logger->error('Tenant budget exceeded', [
                'tenant_id' => $tenantId,
                'budget' => $budget,
                'spent' => $spent,
                'required' => $estimatedCost,
                'remaining' => $remaining,
            ]);
            
            throw new RuntimeException(
                sprintf('Tenant budget exceeded. Budget: %.2f, Spent: %.2f, Required: %.2f', 
                    $budget, 
                    $spent, 
                    $estimatedCost
                )
            );
        }

        if ($usagePercentage >= self::ALERT_THRESHOLD_CRITICAL) {
            $this->logger->critical('Budget critical threshold reached', [
                'tenant_id' => $tenantId,
                'percentage' => $usagePercentage,
                'budget' => $budget,
                'spent' => $spent,
            ]);
        } elseif ($usagePercentage >= self::ALERT_THRESHOLD_WARNING) {
            $this->logger->warning('Budget warning threshold reached', [
                'tenant_id' => $tenantId,
                'percentage' => $usagePercentage,
                'budget' => $budget,
                'spent' => $spent,
            ]);
        }

        $this->logger->debug('Budget check passed', [
            'tenant_id' => $tenantId,
            'estimated_cost' => $estimatedCost,
            'remaining' => $remaining,
            'usage_percentage' => $usagePercentage,
        ]);

        return true;
    }

    /**
     * Record spend after successful AI operation
     *
     * @param int $tenantId Tenant ID
     * @param int $userId User ID
     * @param string $operation Operation type
     * @param float $cost Cost in currency
     */
    public function recordSpend(int $tenantId, int $userId, string $operation, float $cost): void
    {
        $key = self::SPEND_PREFIX . $tenantId;
        $currentSpend = $this->cache->get($key, 0.0);
        $this->cache->put($key, $currentSpend + $cost, self::DEFAULT_TTL);

        $this->db->table('ai_spend_logs')->insert([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'operation' => $operation,
            'cost' => $cost,
            'created_at' => $this->carbon->now(),
        ]);
        
        $this->logger->debug('Spend recorded', [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'operation' => $operation,
            'cost' => $cost,
        ]);
    }

    /**
     * Get tenant monthly budget
     */
    private function getTenantBudget(int $tenantId): float
    {
        $key = self::BUDGET_PREFIX . $tenantId;
        
        $budget = $this->cache->get($key);
        
        if ($budget === null) {
            $budget = $this->db->table('tenant_ai_budgets')
                ->where('tenant_id', $tenantId)
                ->value('monthly_budget') ?? self::DEFAULT_MONTHLY_BUDGET;

            $this->cache->put($key, $budget, self::DEFAULT_TTL);
        }

        return (float) $budget;
    }

    /**
     * Get tenant current spend for this month
     */
    private function getTenantSpend(int $tenantId): float
    {
        $key = self::SPEND_PREFIX . $tenantId;
        
        $spend = $this->cache->get($key);
        
        if ($spend === null) {
            $now = $this->carbon->now();
            $spend = $this->db->table('ai_spend_logs')
                ->where('tenant_id', $tenantId)
                ->whereMonth('created_at', $now->month)
                ->whereYear('created_at', $now->year)
                ->sum('cost') ?? 0.00;

            $this->cache->put($key, $spend, self::DEFAULT_TTL);
        }

        return (float) $spend;
    }

    /**
     * Set tenant monthly budget
     */
    public function setTenantBudget(int $tenantId, float $budget): void
    {
        $key = self::BUDGET_PREFIX . $tenantId;
        $this->cache->put($key, $budget, self::DEFAULT_TTL);

        $this->db->table('tenant_ai_budgets')->updateOrInsert(
            ['tenant_id' => $tenantId],
            ['monthly_budget' => $budget, 'updated_at' => $this->carbon->now()]
        );
        
        $this->logger->info('Tenant budget updated', [
            'tenant_id' => $tenantId,
            'budget' => $budget,
        ]);
    }

    /**
     * Get budget status for monitoring
     */
    public function getBudgetStatus(int $tenantId): array
    {
        $budget = $this->getTenantBudget($tenantId);
        $spent = $this->getTenantSpend($tenantId);
        $remaining = max(0, $budget - $spent);
        $percentage = $budget > 0 ? ($spent / $budget) * 100 : 0;

        return [
            'tenant_id' => $tenantId,
            'budget' => round($budget, 2),
            'spent' => round($spent, 2),
            'remaining' => round($remaining, 2),
            'percentage' => round($percentage, 2),
            'near_limit' => $percentage >= self::ALERT_THRESHOLD_WARNING,
            'critical' => $percentage >= self::ALERT_THRESHOLD_CRITICAL,
            'exceeded' => $spent >= $budget,
        ];
    }

    /**
     * Get all tenant budget statuses for admin dashboard
     */
    public function getAllBudgetStatuses(array $tenantIds): array
    {
        $statuses = [];
        
        foreach ($tenantIds as $tenantId) {
            $statuses[$tenantId] = $this->getBudgetStatus($tenantId);
        }
        
        return $statuses;
    }

    /**
     * Reset monthly spend (for monthly reset)
     */
    public function resetMonthlySpend(int $tenantId): void
    {
        $this->cache->forget(self::SPEND_PREFIX . $tenantId);
        
        $this->logger->info('Monthly spend reset', [
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Get cost estimate for operation
     */
    public function getCostEstimate(string $operation): float
    {
        return match ($operation) {
            'vision' => 0.01,
            'chat' => 0.002,
            'embedding' => 0.0001,
            'text_generation' => 0.003,
            'image_generation' => 0.02,
            'audio_transcription' => 0.006,
            default => 0.01,
        };
    }

    /**
     * Get spend breakdown by operation for billing
     */
    public function getSpendBreakdown(int $tenantId, string $startDate, string $endDate): array
    {
        return $this->db->table('ai_spend_logs')
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('operation, SUM(cost) as total_cost, COUNT(*) as total_requests, AVG(cost) as avg_cost')
            ->groupBy('operation')
            ->orderByDesc('total_cost')
            ->get()
            ->toArray();
    }

    /**
     * Get daily spend trend for analytics
     */
    public function getDailySpendTrend(int $tenantId, int $days = 30): array
    {
        $startDate = $this->carbon->now()->subDays($days)->startOfDay();
        $endDate = $this->carbon->now()->endOfDay();

        return $this->db->table('ai_spend_logs')
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, SUM(cost) as daily_spend, COUNT(*) as daily_requests')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * Check if tenant has exceeded budget and should be blocked
     */
    public function isBudgetExceeded(int $tenantId): bool
    {
        $status = $this->getBudgetStatus($tenantId);
        return $status['exceeded'];
    }

    /**
     * Get top spending users for a tenant
     */
    public function getTopSpendingUsers(int $tenantId, string $startDate, string $endDate, int $limit = 10): array
    {
        return $this->db->table('ai_spend_logs')
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('user_id, SUM(cost) as total_spend, COUNT(*) as total_requests')
            ->groupBy('user_id')
            ->orderByDesc('total_spend')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly DatabaseManager $db,
        private readonly CarbonInterface $carbon,
        private readonly LoggerInterface $logger
    ) {}
}
