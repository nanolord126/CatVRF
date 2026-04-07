<?php declare(strict_types=1);

namespace App\Filament\Tenant\Widgets;


use Illuminate\Database\DatabaseManager;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

/**
 * WalletBalanceWidget — баланс кошелька tenant'а.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Показывает: текущий баланс / удержание / доступно к выводу.
 * Данные из таблицы wallets (tenant-scoped).
 */
final class WalletBalanceWidget extends StatsOverviewWidget
{
    public function __construct(
        private readonly DatabaseManager $db,
    ) {}

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $tenantId = tenant()?->id;

        if (!$tenantId) {
            return [
                Stat::make('Баланс', '–')->color('gray'),
            ];
        }

        $wallet = $this->db->table('wallets')
            ->where('tenant_id', $tenantId)
            ->whereNull('business_group_id')
            ->first();

        $balance     = $wallet ? (float) $wallet->current_balance / 100 : 0;
        $hold        = $wallet ? (float) $wallet->hold_amount / 100       : 0;
        $available   = max(0, $balance - $hold);

        // Тренд: последние 7 дней пополнений (deposit)
        $depositsTrend = $this->db->table('balance_transactions')
            ->where('wallet_id', $wallet?->id ?? 0)
            ->where('type', 'deposit')
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw("DATE(created_at) as day, SUM(amount)/100 as total")
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total')
            ->map(static fn ($v) => (float) $v)
            ->toArray();

        // Тренд: последние 7 дней выплат (payout)
        $payoutsTrend = $this->db->table('balance_transactions')
            ->where('wallet_id', $wallet?->id ?? 0)
            ->where('type', 'payout')
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw("DATE(created_at) as day, SUM(amount)/100 as total")
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total')
            ->map(static fn ($v) => (float) $v)
            ->toArray();

        return [
            Stat::make('Баланс кошелька', number_format($balance, 2, '.', ' ') . ' ₽')
                ->description('Текущий баланс счёта')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color($balance > 0 ? 'success' : 'warning')
                ->chart($depositsTrend ?: [0]),

            Stat::make('Удержано (Hold)', number_format($hold, 2, '.', ' ') . ' ₽')
                ->description('Заблокировано под активные заказы')
                ->descriptionIcon('heroicon-o-lock-closed')
                ->color($hold > 0 ? 'warning' : 'gray'),

            Stat::make('Доступно к выводу', number_format($available, 2, '.', ' ') . ' ₽')
                ->description('Свободные средства')
                ->descriptionIcon('heroicon-o-arrow-up-tray')
                ->color($available > 0 ? 'primary' : 'gray')
                ->chart($payoutsTrend ?: [0]),
        ];
    }
}
