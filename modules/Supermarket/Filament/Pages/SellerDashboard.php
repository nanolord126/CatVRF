<?php

declare(strict_types=1);

namespace Modules\Supermarket\Filament\Pages;

use App\Models\Tenant;
use Modules\Supermarket\Application\Services\SellerAnalyticsService;
use Modules\Supermarket\Application\Services\AIInsightGenerator;
use Filament\Pages\Page;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class SellerDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Дашборд';
    protected static ?string $navigationGroup = 'Supermarket';
    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.supermarket.pages.seller-dashboard';

    public array $analytics = [];
    public array $insights = [];
    public string $period = '30d';

    public function mount(): void
    {
        $seller = Auth::user()->tenant;
        
        if (!$seller) {
            abort(403);
        }

        $analyticsService = app(SellerAnalyticsService::class);
        $this->analytics = $analyticsService->getDashboard($seller, $this->period);

        $aiInsightGenerator = app(AIInsightGenerator::class);
        $this->insights = $aiInsightGenerator->getCachedInsights($seller)->toArray();
    }

    public function changePeriod(string $period): void
    {
        $this->period = $period;
        $this->mount();
    }

    public function getViewData(): array
    {
        return [
            'analytics' => $this->analytics,
            'insights' => $this->insights,
            'period' => $this->period,
        ];
    }

    protected function getHeaderWidgets(): array
    {
        $analytics = $this->analytics;

        return [
            StatsOverviewWidget::make([
                StatsOverviewWidget\Stat::make('Выручка', number_format($analytics['total_revenue'] ?? 0, 0, ',', ' ') . ' ₽')
                    ->description(($analytics['revenue_trend']['growth_percent'] ?? 0) . '% к прошлому')
                    ->descriptionIcon(($analytics['revenue_trend']['growth_percent'] ?? 0) >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    ->color(($analytics['revenue_trend']['growth_percent'] ?? 0) >= 0 ? 'success' : 'danger'),

                StatsOverviewWidget\Stat::make('Заказов', $analytics['orders_count'] ?? 0)
                    ->description('Средний чек: ' . number_format($analytics['avg_order_value'] ?? 0, 0, ',', ' ') . ' ₽'),

                StatsOverviewWidget\Stat::make('Конверсия', ($analytics['conversion_rate'] ?? 0) . '%')
                    ->description('Из просмотров в заказы'),

                StatsOverviewWidget\Stat::make('Возвраты', ($analytics['returns_rate'] ?? 0) . '%')
                    ->description(number_format($analytics['returns_rate'] ?? 0, 0, ',', ' ') . ' ₽')
                    ->color(($analytics['returns_rate'] ?? 0) > 8 ? 'danger' : 'warning'),
            ]),
        ];
    }
}
