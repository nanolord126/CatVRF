<?php

declare(strict_types=1);

namespace Modules\Payments\Presentation\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Payments\Domain\ValueObjects\PaymentStatus;
use Modules\Payments\Infrastructure\Models\PaymentModel;

/**
 * Filament Widget: Статистика платежей.
 */
final class PaymentStatsWidget extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $tenantId = filament()->getTenant()?->id;

        $base = PaymentModel::query()->where('tenant_id', $tenantId);

        $totalCaptured = (clone $base)
            ->where('status', PaymentStatus::CAPTURED->value)
            ->whereDate('created_at', today())
            ->sum('amount');

        $countToday = (clone $base)
            ->whereDate('created_at', today())
            ->count();

        $countFailed = (clone $base)
            ->where('status', PaymentStatus::FAILED->value)
            ->whereDate('created_at', today())
            ->count();

        $pendingCount = (clone $base)
            ->where('status', PaymentStatus::PENDING->value)
            ->count();

        return [
            Stat::make('Оборот за сегодня', number_format($totalCaptured / 100, 2) . ' ₽')
                ->description('Успешные платежи')
                ->color('success')
                ->icon('heroicon-o-banknotes'),

            Stat::make('Платежей сегодня', $countToday)
                ->description('Всего транзакций')
                ->color('info')
                ->icon('heroicon-o-credit-card'),

            Stat::make('Ошибок сегодня', $countFailed)
                ->description('Неуспешные попытки')
                ->color($countFailed > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-circle'),

            Stat::make('Ожидают оплаты', $pendingCount)
                ->description('Pending платежи')
                ->color('warning')
                ->icon('heroicon-o-clock'),
        ];
    }
}
