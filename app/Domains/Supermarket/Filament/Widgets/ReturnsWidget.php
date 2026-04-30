<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class ReturnsWidget extends BaseWidget
{
    public function __construct(
        public string $period = '30d',
        public array $analytics = []
    ) {
        parent::__construct();
    }

    protected function getStats(): array
    {
        $returnsRate = $this->analytics['returns_rate'] ?? 0;
        $returnsAmount = $this->analytics['returns_amount'] ?? 0;
        
        $color = $returnsRate > 8 ? 'danger' : ($returnsRate > 5 ? 'warning' : 'success');
        
        return [
            Stat::make('Возвраты', number_format($returnsRate, 1) . '%')
                ->description(number_format($returnsAmount, 0, ',', ' ') . ' ₽')
                ->descriptionIcon('heroicon-o-arrow-uturn-left')
                ->color($color)
                ->chart([5, 6, 5, 7, 6, 8, 7]),
        ];
    }
}
