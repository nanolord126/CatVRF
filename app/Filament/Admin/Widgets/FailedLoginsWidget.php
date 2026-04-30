<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use Carbon\CarbonImmutable;

use Illuminate\Database\DatabaseManager;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Виджет — неудачные попытки входа за 24 часа.
 * Канон CatVRF 2026: Admin Panel → SecurityDashboard.
 */
final class FailedLoginsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public function __construct(
        private readonly DatabaseManager $db,
    ) {}

    protected function getStats(): array
    {
        $last24h = CarbonImmutable::now()->subHours(24);
        $last1h  = CarbonImmutable::now()->subHour();

        $total24h = $this->db->table('fraud_attempts')
            ->where('operation_type', 'login')
            ->where('decision', 'block')
            ->where('created_at', '>=', $last24h)
            ->count();

        $last1hCount = $this->db->table('fraud_attempts')
            ->where('operation_type', 'login')
            ->where('decision', 'block')
            ->where('created_at', '>=', $last1h)
            ->count();

        $uniqueIPs = $this->db->table('fraud_attempts')
            ->where('operation_type', 'login')
            ->where('created_at', '>=', $last24h)
            ->distinct('ip_address')
            ->count('ip_address');

        // Тренд: последние 7 дней
        $trend = $this->db->table('fraud_attempts')
            ->where('operation_type', 'login')
            ->where('decision', 'block')
            ->where('created_at', '>=', CarbonImmutable::now()->subDays(7))
            ->selectRaw('DATE(created_at) as day, COUNT(*) as cnt')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('cnt')
            ->toArray();

        return [
            Stat::make('Заблокировано входов (24ч)', $total24h)
                ->description("За последний час: {$last1hCount}")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($total24h > 50 ? 'danger' : ($total24h > 10 ? 'warning' : 'success'))
                ->chart($trend),

            Stat::make('Уникальных IP (24ч)', $uniqueIPs)
                ->description('Источников атак')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color($uniqueIPs > 20 ? 'danger' : 'warning'),

            Stat::make('Brute-force сейчас', $last1hCount)
                ->description($last1hCount > 5 ? '⚠ Возможная атака' : 'Норма')
                ->descriptionIcon('heroicon-m-shield-exclamation')
                ->color($last1hCount > 5 ? 'danger' : 'success'),
        ];
    }
}
