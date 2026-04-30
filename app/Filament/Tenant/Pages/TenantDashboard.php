<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Pages;

use Carbon\CarbonImmutable;

use Illuminate\Database\DatabaseManager;
use App\Filament\Tenant\Widgets\WalletBalanceWidget;
use App\Filament\Tenant\Widgets\OrdersStatsWidget;
use App\Filament\Tenant\Widgets\AIConstructorUsageWidget;
use Filament\Pages\Page;
use Filament\Actions\Action;

/**
 * TenantDashboard — главный дашборд бизнеса в Tenant Panel.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Метрики: GMV, заказы, новые клиенты, AI-использование, Wallet.
 * Обновление через wire:poll.30000ms.
 * Tenant-scoped: все данные фильтруются по tenant_id.
 */
final class TenantDashboard extends Page
{
    // ── Метрики ──────────────────────────────────────────────
    public float $gmvToday     = 0;

    public float $gmv30d       = 0;

    public int $ordersToday  = 0;

    public int $orders30d    = 0;

    public int $newUsersToday = 0;

    public int $aiUsageToday  = 0;

    public float $walletBalance = 0;

    public array $topVerticals  = [];

    public array $recentOrders  = [];

    public string $period       = '30d';

    protected static ?string $navigationIcon  = 'heroicon-o-chart-pie';

    protected static ?string $navigationLabel = 'Дашборд';

    protected static ?string $slug            = 'dashboard';

    protected static ?int $navigationSort  = 1;

    protected static string $view            = 'filament.tenant.pages.tenant-dashboard';

    public function __construct(
        private readonly DatabaseManager $db,
    ) {}

    public function mount(): void
    {
        $this->loadMetrics();
    }

    public function refresh(): void
    {
        $this->loadMetrics();
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;
        $this->loadMetrics();
    }

    public function getWidgets(): array
    {
        return [
            WalletBalanceWidget::class,
            OrdersStatsWidget::class,
            AIConstructorUsageWidget::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 3;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Обновить')
                ->icon('heroicon-o-arrow-path')
                ->action('refresh'),
        ];
    }

    private function loadMetrics(): void
    {
        $tenantId = tenant()?->id;
        if (! $tenantId) {
            return;
        }

        $since30d = CarbonImmutable::now()->subDays(30);
        $today    = CarbonImmutable::now()->startOfDay();

        // GMV
        $this->gmvToday = (float) $this->db->table('orders')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $today)
            ->whereIn('status', ['completed', 'processing', 'shipped'])
            ->sum('total_amount') / 100;

        $this->gmv30d = (float) $this->db->table('orders')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $since30d)
            ->whereIn('status', ['completed', 'processing', 'shipped'])
            ->sum('total_amount') / 100;

        // Заказы
        $this->ordersToday = $this->db->table('orders')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $today)
            ->count();

        $this->orders30d = $this->db->table('orders')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $since30d)
            ->count();

        // Новые пользователи сегодня (через первый заказ)
        $this->newUsersToday = $this->db->table('orders')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $today)
            ->distinct('user_id')
            ->count('user_id');

        // AI-использование сегодня
        $this->aiUsageToday = $this->db->table('user_ai_designs')
            ->where('created_at', '>=', $today)
            ->whereIn('user_id', static function ($q) use ($tenantId) {
                $q->select('user_id')->from('orders')->where('tenant_id', $tenantId);
            })
            ->count();

        // Wallet balance
        $wallet = $this->db->table('wallets')
            ->where('tenant_id', $tenantId)
            ->whereNull('business_group_id')
            ->first();

        $this->walletBalance = $wallet ? (float) $wallet->current_balance / 100 : 0;

        // Топ вертикали
        $this->topVerticals = $this->db->table('orders')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $since30d)
            ->whereNotNull('vertical')
            ->selectRaw('vertical, COUNT(*) as cnt, SUM(total_amount) as gmv')
            ->groupBy('vertical')
            ->orderByDesc('gmv')
            ->limit(5)
            ->get()
            ->map(static fn ($r) => (array) $r)
            ->toArray();

        // Последние 10 заказов
        $this->recentOrders = $this->db->table('orders')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'uuid', 'status', 'total_amount', 'vertical', 'created_at'])
            ->map(static fn ($r) => (array) $r)
            ->toArray();
    }
}
