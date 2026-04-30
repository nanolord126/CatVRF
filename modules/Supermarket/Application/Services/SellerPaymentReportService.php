<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\Services;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use App\Services\FraudControlService;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

final class SellerPaymentReportService
{
    use WithAuditLogging;
    use WithTelemetry;

    private readonly FraudControlService $fraudControl;

    public function __construct(FraudControlService $fraudControl)
    {
        $this->fraudControl = $fraudControl;
    }
    public function getReport(Tenant $seller, string $period = '30d', array $filters = []): array
    {
        return $this->withSpan(
            'seller_payment_report.get',
            function () use ($seller, $period, $filters) {
                $from = $this->getPeriodStart($period);
                
                return [
            'period' => $period,
            'from' => $from->toIso8601String(),
            'to' => now()->toIso8601String(),
            
            'kpis' => $this->getKPIs($seller, $from),
            'revenue_trend' => $this->getRevenueTrend($seller, $from),
            'payment_methods' => $this->getPaymentMethodsBreakdown($seller, $from),
            'by_subvertical' => $this->getBySubVertical($seller, $from),
            'subscriptions' => $this->getSubscriptionStats($seller, $from),
            'refunds' => $this->getRefundStats($seller, $from),
            'b2b' => $this->getB2BStats($seller, $from),
                ];
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'seller_payment_report_get',
                userId: (string) $seller->owner_id ?? null,
                tenantId: (string) $seller->id,
            ),
        );
    }

    private function getPeriodStart(string $period): Carbon
    {
        return match ($period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            '1m' => now()->subMonth(),
            '3m' => now()->subMonths(3),
            '1y' => now()->subYear(),
            default => now()->subDays(30),
        };
    }

    private function getKPIs(Tenant $seller, Carbon $from): array
    {
        $totalRevenue = DB::table('supermarket_orders')
            ->where('seller_id', $seller->id)
            ->where('status', 'delivered')
            ->where('created_at', '>=', $from)
            ->sum('total_amount');

        $commissionRate = config('supermarket.commission_rate', 0.05);
        $platformCommission = $totalRevenue * $commissionRate;
        $gatewayCommission = $totalRevenue * 0.02;
        $received = $totalRevenue - $platformCommission - $gatewayCommission;

        $refunds = DB::table('supermarket_returns')
            ->where('seller_id', $seller->id)
            ->where('status', 'completed')
            ->where('created_at', '>=', $from)
            ->sum('refund_amount');

        return [
            'total_revenue' => (float) $totalRevenue,
            'received' => (float) $received,
            'platform_commission' => (float) $platformCommission,
            'gateway_commission' => (float) $gatewayCommission,
            'avg_commission_percent' => round(($commissionRate + 0.02) * 100, 2),
            'refunds' => (float) $refunds,
            'refunds_percent' => $totalRevenue > 0 ? round(($refunds / $totalRevenue) * 100, 2) : 0,
        ];
    }

    private function getRevenueTrend(Tenant $seller, Carbon $from): array
    {
        $previousFrom = $from->copy()->subDays($from->diffInDays(now()));
        
        $currentRevenue = DB::table('supermarket_orders')
            ->where('seller_id', $seller->id)
            ->where('status', 'delivered')
            ->whereBetween('created_at', [$from, now()])
            ->sum('total_amount');

        $previousRevenue = DB::table('supermarket_orders')
            ->where('seller_id', $seller->id)
            ->where('status', 'delivered')
            ->whereBetween('created_at', [$previousFrom, $from])
            ->sum('total_amount');

        $growth = $previousRevenue > 0 ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 : 0;

        return [
            'current' => (float) $currentRevenue,
            'previous' => (float) $previousRevenue,
            'growth_percent' => round($growth, 2),
            'daily' => $this->getDailyRevenue($seller, $from),
        ];
    }

    private function getDailyRevenue(Tenant $seller, Carbon $from): array
    {
        return DB::table('supermarket_orders')
            ->where('seller_id', $seller->id)
            ->where('status', 'delivered')
            ->where('created_at', '>=', $from)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('COUNT(*) as orders')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    private function getPaymentMethodsBreakdown(Tenant $seller, Carbon $from): array
    {
        return DB::table('supermarket_orders')
            ->where('seller_id', $seller->id)
            ->where('status', 'delivered')
            ->where('created_at', '>=', $from)
            ->select('payment_method', DB::raw('SUM(total_amount) as amount'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get()
            ->toArray();
    }

    private function getBySubVertical(Tenant $seller, Carbon $from): array
    {
        return DB::table('supermarket_orders')
            ->where('seller_id', $seller->id)
            ->where('created_at', '>=', $from)
            ->select('sub_vertical', DB::raw('SUM(total_amount) as revenue'), DB::raw('COUNT(*) as orders'))
            ->groupBy('sub_vertical')
            ->orderByDesc('revenue')
            ->get()
            ->toArray();
    }

    private function getSubscriptionStats(Tenant $seller, Carbon $from): array
    {
        $activeCount = DB::table('supermarket_subscriptions')
            ->where('seller_id', $seller->id)
            ->where('status', 'active')
            ->count();

        $subscriptionRevenue = DB::table('supermarket_orders')
            ->where('seller_id', $seller->id)
            ->where('is_subscription', true)
            ->where('created_at', '>=', $from)
            ->sum('total_amount');

        $successfulPayments = DB::table('supermarket_subscription_payments')
            ->join('supermarket_subscriptions', 'supermarket_subscriptions.id', '=', 'supermarket_subscription_payments.subscription_id')
            ->where('supermarket_subscriptions.seller_id', $seller->id)
            ->where('supermarket_subscription_payments.status', 'success')
            ->where('supermarket_subscription_payments.created_at', '>=', $from)
            ->count();

        $failedPayments = DB::table('supermarket_subscription_payments')
            ->join('supermarket_subscriptions', 'supermarket_subscriptions.id', '=', 'supermarket_subscription_payments.subscription_id')
            ->where('supermarket_subscriptions.seller_id', $seller->id)
            ->where('supermarket_subscription_payments.status', 'failed')
            ->where('supermarket_subscription_payments.created_at', '>=', $from)
            ->count();

        return [
            'active_subscriptions' => $activeCount,
            'revenue' => (float) $subscriptionRevenue,
            'successful_payments' => $successfulPayments,
            'failed_payments' => $failedPayments,
            'success_rate' => ($successfulPayments + $failedPayments) > 0 
                ? round(($successfulPayments / ($successfulPayments + $failedPayments)) * 100, 2)
                : 0,
        ];
    }

    private function getRefundStats(Tenant $seller, Carbon $from): array
    {
        $refunds = DB::table('supermarket_returns')
            ->where('seller_id', $seller->id)
            ->where('created_at', '>=', $from)
            ->select('status', DB::raw('SUM(refund_amount) as amount'), DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->toArray();

        return [
            'by_status' => $refunds,
            'total_amount' => array_sum(array_column($refunds, 'amount')),
            'total_count' => array_sum(array_column($refunds, 'count')),
        ];
    }

    private function getB2BStats(Tenant $seller, Carbon $from): array
    {
        $b2bRevenue = DB::table('supermarket_orders')
            ->where('seller_id', $seller->id)
            ->where('is_b2b', true)
            ->where('created_at', '>=', $from)
            ->sum('total_amount');

        $b2cRevenue = DB::table('supermarket_orders')
            ->where('seller_id', $seller->id)
            ->where('is_b2b', false)
            ->where('created_at', '>=', $from)
            ->sum('total_amount');

        return [
            'b2b_revenue' => (float) $b2bRevenue,
            'b2c_revenue' => (float) $b2cRevenue,
            'b2b_share' => ($b2bRevenue + $b2cRevenue) > 0 
                ? round(($b2bRevenue / ($b2bRevenue + $b2cRevenue)) * 100, 2)
                : 0,
        ];
    }
}
