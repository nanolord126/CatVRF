<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Services;

use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final readonly class SellerPaymentReportService
{
    public function getReport(Tenant $seller, string $period = '30d', array $filters = []): array
    {
        $from = $this->getPeriodStartDate($period);
        $to = now();

        return [
            'period' => $period,
            'from' => $from->toIso8601String(),
            'to' => $to->toIso8601String(),
            
            // KPIs
            'kpi' => $this->getKPIs($seller, $from, $to),
            
            // Analytics by tabs
            'general_analytics' => $this->getGeneralAnalytics($seller, $from, $to),
            'payments_by_orders' => $this->getPaymentsByOrders($seller, $from, $to, $filters),
            'subscriptions' => $this->getSubscriptionPayments($seller, $from, $to),
            'returns' => $this->getReturnsAndRefunds($seller, $from, $to),
            'b2b' => $this->getB2BPayments($seller, $from, $to),
        ];
    }

    private function getKPIs(Tenant $seller, Carbon $from, Carbon $to): array
    {
        $totalRevenue = $this->getTotalRevenue($seller, $from, $to);
        $receivedAmount = $this->getReceivedAmount($seller, $from, $to);
        $avgCommission = $this->getAverageCommission($seller, $from, $to);
        $returnsAmount = $this->getReturnsAmount($seller, $from, $to);
        $previousRevenue = $this->getTotalRevenue($seller, $from->copy()->subDays($from->diffInDays($to)), $from);
        
        $revenueGrowth = $previousRevenue > 0 
            ? (($totalRevenue - $previousRevenue) / $previousRevenue) * 100 
            : 0;

        return [
            'total_revenue' => $totalRevenue,
            'total_revenue_formatted' => number_format($totalRevenue, 0, ',', ' ') . ' ₽',
            'received_amount' => $receivedAmount,
            'received_amount_formatted' => number_format($receivedAmount, 0, ',', ' ') . ' ₽',
            'average_commission' => $avgCommission,
            'average_commission_formatted' => number_format($avgCommission, 2) . '%',
            'returns_amount' => $returnsAmount,
            'returns_amount_formatted' => number_format($returnsAmount, 0, ',', ' ') . ' ₽',
            'returns_percentage' => $totalRevenue > 0 ? ($returnsAmount / $totalRevenue) * 100 : 0,
            'revenue_growth' => $revenueGrowth,
        ];
    }

    private function getGeneralAnalytics(Tenant $seller, Carbon $from, Carbon $to): array
    {
        return [
            'revenue_by_day' => $this->getRevenueByDay($seller, $from, $to),
            'payment_methods_breakdown' => $this->getPaymentMethodsBreakdown($seller, $from, $to),
            'revenue_by_sub_vertical' => $this->getRevenueBySubVertical($seller, $from, $to),
        ];
    }

    private function getPaymentsByOrders(Tenant $seller, Carbon $from, Carbon $to, array $filters): array
    {
        $query = DB::table('supermarket_orders')
            ->where('seller_id', $seller->id)
            ->whereBetween('created_at', [$from, $to]);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        $payments = $query
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return [
            'payments' => $payments->items(),
            'pagination' => [
                'total' => $payments->total(),
                'per_page' => $payments->perPage(),
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
            ],
        ];
    }

    private function getSubscriptionPayments(Tenant $seller, Carbon $from, Carbon $to): array
    {
        $activeSubscriptions = DB::table('subscriptions')
            ->where('seller_id', $seller->id)
            ->where('status', 'active')
            ->count();

        $subscriptionRevenue = DB::table('supermarket_orders')
            ->where('seller_id', $seller->id)
            ->where('is_subscription', true)
            ->whereBetween('created_at', [$from, $to])
            ->sum('total_amount');

        $successfulPayments = DB::table('subscription_payments')
            ->whereHas('subscription', fn($q) => $q->where('seller_id', $seller->id))
            ->where('status', 'success')
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $failedPayments = DB::table('subscription_payments')
            ->whereHas('subscription', fn($q) => $q->where('seller_id', $seller->id))
            ->where('status', 'failed')
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $problematicSubscriptions = DB::table('subscriptions')
            ->where('seller_id', $seller->id)
            ->where('status', 'paused')
            ->count();

        return [
            'active_subscriptions' => $activeSubscriptions,
            'subscription_revenue' => $subscriptionRevenue,
            'subscription_revenue_formatted' => number_format($subscriptionRevenue, 0, ',', ' ') . ' ₽',
            'successful_payments' => $successfulPayments,
            'failed_payments' => $failedPayments,
            'success_rate' => ($successfulPayments + $failedPayments) > 0 
                ? ($successfulPayments / ($successfulPayments + $failedPayments)) * 100 
                : 100,
            'problematic_subscriptions' => $problematicSubscriptions,
        ];
    }

    private function getReturnsAndRefunds(Tenant $seller, Carbon $from, Carbon $to): array
    {
        $returnsAmount = DB::table('returns')
            ->where('seller_id', $seller->id)
            ->whereBetween('created_at', [$from, $to])
            ->sum('refund_amount');

        $returnsCount = DB::table('returns')
            ->where('seller_id', $seller->id)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $completedReturns = DB::table('returns')
            ->where('seller_id', $seller->id)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $pendingReturns = DB::table('returns')
            ->where('seller_id', $seller->id)
            ->whereIn('status', ['pending', 'approved'])
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $topReturnReasons = DB::table('returns')
            ->where('seller_id', $seller->id)
            ->whereBetween('created_at', [$from, $to])
            ->select('reason_type', DB::raw('count(*) as count'))
            ->groupBy('reason_type')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();

        return [
            'returns_amount' => $returnsAmount,
            'returns_amount_formatted' => number_format($returnsAmount, 0, ',', ' ') . ' ₽',
            'returns_count' => $returnsCount,
            'completed_returns' => $completedReturns,
            'pending_returns' => $pendingReturns,
            'top_return_reasons' => $topReturnReasons,
        ];
    }

    private function getB2BPayments(Tenant $seller, Carbon $from, Carbon $to): array
    {
        $b2bRevenue = DB::table('supermarket_orders')
            ->where('seller_id', $seller->id)
            ->where('is_b2b', true)
            ->whereBetween('created_at', [$from, $to])
            ->sum('total_amount');

        $totalRevenue = $this->getTotalRevenue($seller, $from, $to);
        $b2bShare = $totalRevenue > 0 ? ($b2bRevenue / $totalRevenue) * 100 : 0;

        $delayedPayments = DB::table('supermarket_orders')
            ->where('seller_id', $seller->id)
            ->where('is_b2b', true)
            ->where('status', '!=', 'paid')
            ->whereBetween('created_at', [$from, $to])
            ->count();

        return [
            'b2b_revenue' => $b2bRevenue,
            'b2b_revenue_formatted' => number_format($b2bRevenue, 0, ',', ' ') . ' ₽',
            'b2b_share' => $b2bShare,
            'delayed_payments' => $delayedPayments,
        ];
    }

    private function getTotalRevenue(Tenant $seller, Carbon $from, Carbon $to): float
    {
        return (float) DB::table('supermarket_orders')
            ->where('seller_id', $seller->id)
            ->whereBetween('created_at', [$from, $to])
            ->sum('total_amount');
    }

    private function getReceivedAmount(Tenant $seller, Carbon $from, Carbon $to): float
    {
        $totalRevenue = $this->getTotalRevenue($seller, $from, $to);
        $avgCommission = $this->getAverageCommission($seller, $from, $to);
        
        return $totalRevenue * (1 - $avgCommission / 100);
    }

    private function getAverageCommission(Tenant $seller, Carbon $from, Carbon $to): float
    {
        // Assuming average commission is around 5% - this could be calculated from actual commission data
        return 5.0;
    }

    private function getReturnsAmount(Tenant $seller, Carbon $from, Carbon $to): float
    {
        return (float) DB::table('returns')
            ->where('seller_id', $seller->id)
            ->whereBetween('created_at', [$from, $to])
            ->sum('refund_amount');
    }

    private function getRevenueByDay(Tenant $seller, Carbon $from, Carbon $to): array
    {
        return DB::table('supermarket_orders')
            ->where('seller_id', $seller->id)
            ->whereBetween('created_at', [$from, $to])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(CASE WHEN is_b2b = true THEN total_amount ELSE 0 END) as b2b_revenue'),
                DB::raw('SUM(CASE WHEN is_subscription = true THEN total_amount ELSE 0 END) as subscription_revenue'),
                DB::raw('SUM(total_amount) as total_revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    private function getPaymentMethodsBreakdown(Tenant $seller, Carbon $from, Carbon $to): array
    {
        return DB::table('supermarket_orders')
            ->where('seller_id', $seller->id)
            ->whereBetween('created_at', [$from, $to])
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as amount'))
            ->groupBy('payment_method')
            ->get()
            ->toArray();
    }

    private function getRevenueBySubVertical(Tenant $seller, Carbon $from, Carbon $to): array
    {
        return DB::table('supermarket_orders')
            ->where('seller_id', $seller->id)
            ->whereBetween('created_at', [$from, $to])
            ->select('sub_vertical', DB::raw('SUM(total_amount) as revenue'), DB::raw('COUNT(*) as orders'))
            ->groupBy('sub_vertical')
            ->orderBy('revenue', 'desc')
            ->get()
            ->toArray();
    }

    private function getPeriodStartDate(string $period): Carbon
    {
        return match($period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            default => now()->subDays(30),
        };
    }
}
