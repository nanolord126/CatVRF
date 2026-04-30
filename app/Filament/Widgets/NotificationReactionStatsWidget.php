<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\NotificationAnalyticsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

final class NotificationReactionStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '60s';

    public function __construct(
        private readonly NotificationAnalyticsService $analyticsService,
    ) {}

    protected function getStats(): array
    {
        $tenantId = Auth::user()?->tenant_id ?? 0;

        $stats = $this->analyticsService->getReactionStats($tenantId);
        $statsByChannel = $this->analyticsService->getReactionStatsByChannel($tenantId);

        return [
            Stat::make('Total Reactions', $stats['total_reactions'])
                ->description('All user reactions')
                ->descriptionIcon('heroicon-m-hand-thumb-up')
                ->chart($this->getReactionTrend($tenantId)),

            Stat::make('Positive Reactions', $stats['positive_reactions'])
                ->description('Helpful/Like')
                ->descriptionIcon('heroicon-m-face-smile')
                ->color('success'),

            Stat::make('Negative Reactions', $stats['negative_reactions'])
                ->description('Dislike/Reported')
                ->descriptionIcon('heroicon-m-face-frown')
                ->color('danger'),

            Stat::make('Reaction Rate', $stats['reaction_rate'] . '%')
                ->description('Reactions per delivered')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary'),
        ];
    }

    private function getReactionTrend(int $tenantId): array
    {
        // Get last 7 days trend
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $period = [
                'from' => $date->startOfDay()->toDateTimeString(),
                'to' => $date->endOfDay()->toDateTimeString(),
            ];
            $stats = $this->analyticsService->getReactionStats($tenantId, $period);
            $trend[] = $stats['total_reactions'];
        }

        return $trend;
    }
}
