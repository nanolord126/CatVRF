<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\B2BOrder;
use App\Models\B2BInvoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class B2BAnalyticsWidget extends BaseWidget
{
    protected ?string $heading = 'B2B Analytics Overview';
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalTurnover = B2BOrder::where('status', 'paid')->sum('amount');
        $totalReceivables = B2BInvoice::where('status', 'unpaid')->sum('amount');
        $overdueReceivables = B2BInvoice::where('status', 'unpaid')
            ->where('due_date', '<', now())
            ->sum('amount');

        return [
            Stat::make('Total B2B Turnover', '₽ ' . number_format($totalTurnover, 2))
                ->description('Settled B2B payments')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make('Total Receivables', '₽ ' . number_format($totalReceivables, 2))
                ->description('Outstanding invoices')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),
            Stat::make('Overdue Receivables', '₽ ' . number_format($overdueReceivables, 2))
                ->description('Past due date')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
