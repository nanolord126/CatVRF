<?php

declare(strict_types=1);

namespace Modules\Wallet\Presentation\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Modules\Wallet\Infrastructure\Models\WalletModel;
use Modules\Wallet\Infrastructure\Models\WalletTransactionModel;

/**
 * Filament Widget: сводная статистика кошельков за сегодня.
 */
final class WalletStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalBalance = WalletModel::sum('balance');

        $todayDeposits = WalletTransactionModel::where('type', 'deposit')
            ->whereDate('created_at', today())
            ->sum('amount');

        $todayWithdrawals = WalletTransactionModel::where('type', 'withdraw')
            ->whereDate('created_at', today())
            ->sum('amount');

        $totalHold = (int) WalletModel::selectRaw("SUM(CAST(meta->>'hold_amount' AS bigint)) AS total_hold")
            ->value('total_hold');

        return [
            Stat::make('Общий баланс', number_format($totalBalance / 100, 2) . ' ₽')
                ->description('Сумма по всем кошелькам')
                ->color('success')
                ->icon('heroicon-o-banknotes'),

            Stat::make('Пополнения за сегодня', number_format($todayDeposits / 100, 2) . ' ₽')
                ->description('Всего за ' . today()->format('d.m.Y'))
                ->color('info')
                ->icon('heroicon-o-arrow-trending-up'),

            Stat::make('Выводы за сегодня', number_format($todayWithdrawals / 100, 2) . ' ₽')
                ->description('Всего за ' . today()->format('d.m.Y'))
                ->color('warning')
                ->icon('heroicon-o-arrow-trending-down'),

            Stat::make('На холде', number_format($totalHold / 100, 2) . ' ₽')
                ->description('Зарезервировано')
                ->color('danger')
                ->icon('heroicon-o-lock-closed'),
        ];
    }
}
