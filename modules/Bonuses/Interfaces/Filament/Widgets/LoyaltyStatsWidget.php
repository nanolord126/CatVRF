<?php

declare(strict_types=1);

namespace Modules\Bonuses\Interfaces\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Bonuses\Domain\Entities\Bonus;
use Carbon\Carbon;

final class LoyaltyStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalBonuses = Bonus::sum('amount');
        $bonusesThisMonth = Bonus::where('created_at', '>=', Carbon::now()->startOfMonth())->sum('amount');
        $activeReferrals = 0; // Placeholder for referral logic

        return [
            Stat::make('Total Bonuses Awarded', number_format($totalBonuses / 100, 2) . ' USD')
                ->description('Total amount of bonuses given to users')
                ->color('success'),
            Stat::make('Bonuses This Month', number_format($bonusesThisMonth / 100, 2) . ' USD')
                ->description('Bonuses awarded in the current month')
                ->color('info'),
            Stat::make('Active Referrals', $activeReferrals)
                ->description('Users who have an active referral link')
                ->color('warning'),
        ];
    }
}
