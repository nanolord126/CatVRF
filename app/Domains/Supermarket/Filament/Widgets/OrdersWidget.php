<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class OrdersWidget extends BaseWidget
{
    public function __construct(
        public string $period = '30d',
        public array $analytics = []
    ) {
        parent::__construct();
    }

    protected function getStats(): array
    {
        $ordersCount = $this->analytics['orders_count'] ?? 0;
        $aov = $this->analytics['avg_order_value'] ?? 0;
        
        return [
            Stat::make('Заказы', number_format($ordersCount))
                ->description('Средний чек: ' . number_format($aov, 0, ',', ' ') . ' ₽')
                ->descriptionIcon('heroicon-o-shopping-cart')
                ->color('primary')
                ->chart([10, 15, 13, 17, 20, 25, 30]),
        ];
    }
}
