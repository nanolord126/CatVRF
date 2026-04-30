<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class RevenueWidget extends BaseWidget
{
    public function __construct(
        public string $period = '30d',
        public array $analytics = []
    ) {
        parent::__construct();
    }

    protected function getStats(): array
    {
        $revenue = $this->analytics['total_revenue'] ?? 0;
        $previousRevenue = $this->analytics['previous_total_revenue'] ?? 0;
        
        $growth = 0;
        if ($previousRevenue > 0) {
            $growth = (($revenue - $previousRevenue) / $previousRevenue) * 100;
        }
        
        $growthColor = $growth >= 0 ? 'success' : 'danger';
        $growthIcon = $growth >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down';
        
        return [
            Stat::make('Выручка', number_format($revenue, 0, ',', ' ') . ' ₽')
                ->description($growth >= 0 ? "+{$growth}%" : "{$growth}%")
                ->descriptionIcon($growthIcon)
                ->color($growthColor)
                ->chart([7, 12, 10, 14, 15, 18, 20]),
        ];
    }
}
