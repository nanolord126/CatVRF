<?php

declare(strict_types=1);

namespace App\Domains\Food\Beverages\Services;

use App\Domains\Food\Beverages\Models\BeverageItem;
use App\Domains\Food\Beverages\Models\BeverageOrder;
use App\Domains\Food\Beverages\Models\BeverageShop;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class BeverageAnalyticsService
{
    public function __construct(
        private readonly string $correlationId = '',
    ) {}

    /**
     * Get real-time health metrics for the beverage vertical.
     */
    public function getVerticalHealthMetrics(int $tenantId): array
    {
        $correlationId = $this->correlationId ?: (string) Str::uuid();

        return [
            'total_revenue' => BeverageOrder::where('tenant_id', $tenantId)
                ->where('payment_status', 'captured')
                ->sum('total_price'),
            'active_shops' => BeverageShop::where('tenant_id', $tenantId)
                ->where('status', 'open')
                ->count(),
            'stock_alerts' => BeverageItem::where('tenant_id', $tenantId)
                ->whereRaw('current_stock <= min_stock_threshold')
                ->count(),
            'fraud_prevention_stats' => [
                'high_risk_orders' => BeverageOrder::where('tenant_id', $tenantId)
                    ->where('ml_fraud_score', '>', 0.7)
                    ->count(),
                'avg_fraud_score' => BeverageOrder::where('tenant_id', $tenantId)
                    ->avg('ml_fraud_score') ?? 0,
            ],
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Top selling drinks in the current tenant.
     */
    public function getTopSellingDrinks(int $tenantId, int $limit = 5): Collection
    {
        return BeverageItem::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('total_sales_count', 'desc')
            ->limit($limit)
            ->get();
    }
}
