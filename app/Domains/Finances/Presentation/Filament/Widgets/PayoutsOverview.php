<?php

declare(strict_types=1);

namespace App\Domains\Finances\Presentation\Filament\Widgets;

use Carbon\Carbon;

use App\Domains\Finances\Models\FinanceRecord;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Обзорный виджет выплат.
 *
 * Отображает ключевые метрики по выплатам тенанта:
 * сумма в ожидании, последняя выплата, всего за год.
 * Tenant-scoped через global scope модели.
 *
 * @package App\Domains\Finances\Presentation\Filament\Widgets
 */
final class PayoutsOverview extends BaseWidget
{
    /**
     * Получить статистику.
     *
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $pendingAmount = $this->getPendingPayoutsAmount();
        $lastPayoutAmount = $this->getLastPayoutAmount();
        $yearToDateAmount = $this->getYearToDatePayoutsAmount();

        return [
            Stat::make(
                'Ожидают выплаты',
                $this->formatRubles($pendingAmount),
            )
                ->description('Запланировано на ' . Carbon::now()->addMonth()->startOfMonth()->format('d.m.Y'))
                ->color($pendingAmount > 0 ? 'warning' : 'success'),

            Stat::make(
                'Последняя выплата',
                $this->formatRubles($lastPayoutAmount),
            )
                ->description('Обработано ' . Carbon::now()->startOfMonth()->format('d.m.Y'))
                ->color('success'),

            Stat::make(
                'Выплаты за год',
                $this->formatRubles($yearToDateAmount),
            )
                ->description(Carbon::now()->format('Y') . ' год')
                ->color('primary'),
        ];
    }

    /**
     * Сумма ожидающих выплат (копейки).
     */
    private function getPendingPayoutsAmount(): int
    {
        return (int) FinanceRecord::query()
            ->where('type', 'payout')
            ->where('status', 'pending')
            ->sum('amount');
    }

    /**
     * Сумма последней выплаты (копейки).
     */
    private function getLastPayoutAmount(): int
    {
        return (int) (FinanceRecord::query()
            ->where('type', 'payout')
            ->where('status', 'completed')
            ->latest()
            ->value('amount') ?? 0);
    }

    /**
     * Сумма выплат с начала года (копейки).
     */
    private function getYearToDatePayoutsAmount(): int
    {
        return (int) FinanceRecord::query()
            ->where('type', 'payout')
            ->where('status', 'completed')
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('amount');
    }

    /**
     * Форматировать копейки в рубли.
     */
    private function formatRubles(int $kopecks): string
    {
        return number_format($kopecks / 100, 2, ',', ' ') . ' ₽';
    }
}
