<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Filament\Pages;

use App\Domains\Supermarket\Models\SellerInsight;
use App\Domains\Supermarket\Services\AIInsightGenerator;
use App\Domains\Supermarket\Services\SellerAnalyticsService;
use App\Domains\Supermarket\Services\SellerPaymentReportService;
use Filament\Pages\Page;
use Filament\Widgets\StatsOverviewWidget;
use Illuminate\Support\Facades\Cache;

/**
 * Seller Dashboard - Comprehensive Analytics Dashboard for Supermarket Sellers
 * 
 * Provides sellers with complete visibility into their business performance including:
 * - Revenue analytics (total, by period, growth)
 * - Order analytics (total, status breakdown, conversion)
 * - Payment analytics (methods, success rates, refunds)
 * - Subscription analytics (active, churn, MRR)
 * - B2B analytics (invoices, payment terms, EDO)
 * - Product performance (top sellers, categories, returns)
 * - AI-powered insights and recommendations
 * 
 * @version 2026.1
 */
class SellerDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationGroup = 'Аналитика';
    protected static ?string $title = 'Дашборд магазина';
    protected static string $view = 'filament.supermarket.pages.seller-dashboard';

    public ?string $period = '30d';
    public array $analytics = [];
    public array $insights = [];
    public array $paymentAnalytics = [];
    public array $subscriptionAnalytics = [];

    public function mount(): void
    {
        $this->period = request('period', '30d');
        $this->loadDashboardData();
    }

    public function changePeriod(string $period): void
    {
        $this->period = $period;
        $this->loadDashboardData();
    }

    private function loadDashboardData(): void
    {
        $seller = auth()->user()->tenant;
        
        if (!$seller) {
            return;
        }

        $cacheKey = "seller_dashboard:{$seller->id}:{$this->period}";
        
        $data = Cache::remember($cacheKey, 300, function () use ($seller) {
            $analyticsService = app(SellerAnalyticsService::class);
            $aiInsightGenerator = app(AIInsightGenerator::class);
            $paymentReportService = app(SellerPaymentReportService::class);

            return [
                'analytics' => $analyticsService->getDashboard($seller, $this->period),
                'insights' => $aiInsightGenerator->generateForSeller($seller),
                'payment_analytics' => $paymentReportService->getPaymentReport($seller, $this->period),
                'subscription_analytics' => $analyticsService->getSubscriptionAnalytics($seller, $this->period),
            ];
        });

        $this->analytics = $data['analytics'];
        $this->insights = $data['insights'];
        $this->paymentAnalytics = $data['payment_analytics'];
        $this->subscriptionAnalytics = $data['subscription_analytics'];
    }

    public function getHeaderWidgets(): array
    {
        return [
            StatsOverviewWidget::make([
                \App\Domains\Supermarket\Filament\Widgets\RevenueWidget::make([
                    'period' => $this->period,
                    'analytics' => $this->analytics,
                ]),
                \App\Domains\Supermarket\Filament\Widgets\OrdersWidget::make([
                    'period' => $this->period,
                    'analytics' => $this->analytics,
                ]),
                \App\Domains\Supermarket\Filament\Widgets\AOVWidget::make([
                    'period' => $this->period,
                    'analytics' => $this->analytics,
                ]),
                \App\Domains\Supermarket\Filament\Widgets\ReturnsWidget::make([
                    'period' => $this->period,
                    'analytics' => $this->analytics,
                ]),
            ]),
        ];
    }

    public function getSecondaryWidgets(): array
    {
        return [
            \App\Domains\Supermarket\Filament\Widgets\PaymentMethodsBreakdownWidget::make([
                'period' => $this->period,
                'analytics' => $this->paymentAnalytics,
            ]),
            \App\Domains\Supermarket\Filament\Widgets\RecurringRevenueWidget::make([
                'period' => $this->period,
                'analytics' => $this->subscriptionAnalytics,
            ]),
            \App\Domains\Supermarket\Filament\Widgets\RefundRateWidget::make([
                'period' => $this->period,
                'analytics' => $this->paymentAnalytics,
            ]),
        ];
    }

    public function getFooterWidgets(): array
    {
        return [
            \App\Domains\Supermarket\Filament\Widgets\TopProductsWidget::make([
                'period' => $this->period,
                'analytics' => $this->analytics,
            ]),
            \App\Domains\Supermarket\Filament\Widgets\SubVerticalBreakdownWidget::make([
                'period' => $this->period,
                'analytics' => $this->analytics,
            ]),
            \App\Domains\Supermarket\Filament\Widgets\SubscriptionHealthWidget::make([
                'period' => $this->period,
                'analytics' => $this->subscriptionAnalytics,
            ]),
        ];
    }

    public function getAlertWidgets(): array
    {
        $failedPayments = $this->paymentAnalytics['failed_payments']['count'] ?? 0;
        $refundRate = $this->paymentAnalytics['refund_rate'] ?? 0;
        
        $alerts = [];

        if ($failedPayments > 10) {
            $alerts[] = \App\Domains\Supermarket\Filament\Widgets\FailedPaymentsAlertWidget::make([
                'failed_count' => $failedPayments,
                'period' => $this->period,
            ]);
        }

        if ($refundRate > 5) {
            $alerts[] = \App\Domains\Supermarket\Filament\Widgets\HighRefundRateAlertWidget::make([
                'refund_rate' => $refundRate,
                'period' => $this->period,
            ]);
        }

        return $alerts;
    }

    public function getInsights(): array
    {
        return collect($this->insights)->map(function (SellerInsight $insight) {
            return [
                'id' => $insight->id,
                'title' => $insight->title,
                'description' => $insight->description,
                'impact' => $insight->impact,
                'impact_color' => $insight->getImpactColor(),
                'impact_label' => $insight->getImpactLabel(),
                'actionable' => $insight->actionable,
                'value' => $insight->value,
                'type' => $insight->type,
                'type_label' => $insight->getTypeLabel(),
                'is_active' => $insight->isActive(),
                'created_at' => $insight->created_at?->toIso8601String(),
            ];
        })->toArray();
    }

    public function getPeriodOptions(): array
    {
        return [
            '7d' => '7 дней',
            '30d' => '30 дней',
            '90d' => '90 дней',
            '365d' => '1 год',
        ];
    }

    public function getSelectedPeriodLabel(): string
    {
        return $this->getPeriodOptions()[$this->period] ?? $this->period;
    }

    public function hasB2BOrders(): bool
    {
        return ($this->analytics['b2b_orders_count'] ?? 0) > 0;
    }

    public function hasSubscriptions(): bool
    {
        return ($this->subscriptionAnalytics['active_subscriptions'] ?? 0) > 0;
    }

    public function getPaymentMethodsData(): array
    {
        return $this->paymentAnalytics['payment_methods'] ?? [];
    }

    public function getRevenueByPeriod(): array
    {
        return $this->analytics['revenue_by_period'] ?? [];
    }

    public function getTopCategories(): array
    {
        return $this->analytics['top_categories'] ?? [];
    }

    public function getCustomerRetention(): array
    {
        return $this->analytics['customer_retention'] ?? [];
    }
}
