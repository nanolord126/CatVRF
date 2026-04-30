<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class AOVWidget extends BaseWidget
{
    public function __construct(
        public string $period = '30d',
        public array $analytics = []
    ) {
        parent::__construct();
    }

    protected function getStats(): array
    {
        $conversionRate = $this->analytics['conversion_rate'] ?? 0;
        
        return [
            Stat::make('Конверсия', number_format($conversionRate, 1) . '%')
                ->description('Из просмотров в заказы')
                ->descriptionIcon('heroicon-o-chart-pie')
                ->color('warning')
                ->chart([2.5, 2.8, 3.0, 3.2, 3.5, 3.8, 4.0]),
        ];
    }
}
