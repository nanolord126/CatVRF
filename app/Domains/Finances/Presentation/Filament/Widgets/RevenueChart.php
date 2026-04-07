<?php

declare(strict_types=1);

namespace App\Domains\Finances\Presentation\Filament\Widgets;


use Carbon\Carbon;
use App\Domains\Finances\Models\FinanceRecord;
use Filament\Widgets\ChartWidget;

/**
 * График выручки, выплат и комиссий за последние 30 дней.
 *
 * Tenant-scoped: данные фильтруются
 * через global scope модели FinanceRecord.
 *
 * @package App\Domains\Finances\Presentation\Filament\Widgets
 */
final class RevenueChart extends ChartWidget
{
    /**
     * Заголовок виджета.
     */
    protected static ?string $heading = 'Выручка, выплаты и комиссии';

    /**
     * Получить данные для графика.
     *
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $days = 30;
        $startDate = Carbon::now()->subDays($days)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $labels = [];
        $revenueData = [];
        $payoutData = [];
        $commissionData = [];

        for ($i = 0; $i < $days; $i++) {
            $date = Carbon::now()->subDays($days - 1 - $i);
            $dayStart = $date->copy()->startOfDay();
            $dayEnd = $date->copy()->endOfDay();

            $labels[] = $date->format('d.m');

            $revenueData[] = $this->sumByTypeAndDate('deposit', $dayStart, $dayEnd);
            $payoutData[] = $this->sumByTypeAndDate('payout', $dayStart, $dayEnd);
            $commissionData[] = $this->sumByTypeAndDate('commission', $dayStart, $dayEnd);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Выручка',
                    'data' => $revenueData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.5)',
                    'borderColor' => 'rgb(34, 197, 94)',
                ],
                [
                    'label' => 'Выплаты',
                    'data' => $payoutData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.5)',
                    'borderColor' => 'rgb(239, 68, 68)',
                ],
                [
                    'label' => 'Комиссии',
                    'data' => $commissionData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    /**
     * Тип графика.
     */
    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * Сумма по типу за период (в рублях).
     */
    private function sumByTypeAndDate(string $type, Carbon $from, Carbon $to): float
    {
        $kopecks = (int) FinanceRecord::query()
            ->where('type', $type)
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        return round($kopecks / 100, 2);
    }
}
