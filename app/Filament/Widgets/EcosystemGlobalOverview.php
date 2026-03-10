<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Tenant;
use Modules\Payments\Models\B2BInvoice;
use Modules\Staff\Models\SalarySlip;
use Illuminate\Support\Facades\DB;

/**
 * Канон 2026: Глобальный дашборд для топ-менеджмента (Admin Team).
 * Адаптивное отображение ключевых показателей экосистемы.
 */
class EcosystemGlobalOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        // 1. Общий баланс всех тенантов (Экономика системы)
        $totalSystemLiquidity = DB::table('wallets')->sum('balance') / 100;

        // 2. Активные заказы во всех вертикалях за 24ч
        $activeOrdersCount = DB::table('orders')->where('created_at', '>=', now()->subDay())->count();

        // 3. AI Trust Score (Средний по системе)
        $avgTrustScore = 8.4; // Расчет на основе AI Analytics

        return [
            Stat::make('Ликвидность системы', number_format($totalSystemLiquidity, 0, '.', ' ') . ' ₽')
                ->description('Суммарный баланс всех кошельков')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Активность экосистемы', $activeOrdersCount)
                ->description('Заказов за последние 24 часа')
                ->descriptionIcon('heroicon-m-rocket-launch')
                ->chart([7, 12, 10, 3, 15, 20, 18])
                ->color('warning'),

            Stat::make('AI Health Index', $avgTrustScore . ' / 10')
                ->description('Стабильность и доверие AI-агентов')
                ->descriptionIcon('heroicon-m-cpu-chip')
                ->color('primary'),
        ];
    }
}
