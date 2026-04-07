<?php declare(strict_types=1);

namespace App\Filament\B2B\Pages;


use Illuminate\Database\DatabaseManager;
use App\Filament\B2B\Widgets\CreditLimitWidget;
use App\Filament\B2B\Widgets\B2BOrdersStatsWidget;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\DB;

/**
 * B2BDashboard — главный дашборд B2B-кабинета (юридические лица / ИП).
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Метрики: GMV, оборот, кредитный лимит, статус задолженностей, топ-SKU.
 * Tenant + BusinessGroup scoping.
 * Обновление каждые 60 секунд.
 */
final class B2BDashboard extends Page
{
    public function __construct(
        private readonly DatabaseManager $db,
    ) {}

    protected static ?string $navigationIcon  = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'B2B Дашборд';
    protected static ?string $slug            = 'dashboard';
    protected static ?int    $navigationSort  = 1;
    protected static string  $view            = 'filament.b2b.pages.b2b-dashboard';

    // ── Метрики ──────────────────────────────────────────────
    public float  $gmv30d           = 0;
    public float  $gmv90d           = 0;
    public int    $ordersActive     = 0;
    public int    $ordersPaid30d    = 0;
    public float  $creditLimit      = 0;
    public float  $creditUsed       = 0;
    public float  $creditAvailable  = 0;
    public int    $paymentTermDays  = 14;
    public array  $topProducts      = [];
    public array  $recentOrders     = [];
    public string $businessName     = '';
    public string $b2bTier          = 'standard';

    public function mount(): void
    {
        $this->loadMetrics();
    }

    public function refresh(): void
    {
        $this->loadMetrics();
    }

    private function loadMetrics(): void
    {
        /** @var int|null $businessGroupId */
        $businessGroupId = session('active_business_group_id');

        if (!$businessGroupId) {
            return;
        }

        $group = $this->db->table('business_groups')
            ->where('id', $businessGroupId)
            ->first();

        if (!$group) {
            return;
        }

        $this->businessName    = (string) ($group->legal_name ?? '');
        $this->b2bTier         = (string) ($group->b2b_tier ?? 'standard');
        $this->creditLimit     = (float) ($group->credit_limit ?? 0) / 100;
        $this->creditUsed      = (float) ($group->credit_used ?? 0) / 100;
        $this->creditAvailable = max(0, $this->creditLimit - $this->creditUsed);
        $this->paymentTermDays = (int) ($group->payment_term_days ?? 14);

        $since30d = now()->subDays(30);
        $since90d = now()->subDays(90);

        // GMV
        $this->gmv30d = (float) $this->db->table('orders')
            ->where('business_group_id', $businessGroupId)
            ->where('created_at', '>=', $since30d)
            ->whereIn('status', ['completed', 'processing', 'shipped'])
            ->sum('total_amount') / 100;

        $this->gmv90d = (float) $this->db->table('orders')
            ->where('business_group_id', $businessGroupId)
            ->where('created_at', '>=', $since90d)
            ->whereIn('status', ['completed', 'processing', 'shipped'])
            ->sum('total_amount') / 100;

        // Заказы
        $this->ordersActive = $this->db->table('orders')
            ->where('business_group_id', $businessGroupId)
            ->whereIn('status', ['pending', 'processing', 'shipped'])
            ->count();

        $this->ordersPaid30d = $this->db->table('orders')
            ->where('business_group_id', $businessGroupId)
            ->where('created_at', '>=', $since30d)
            ->where('status', 'completed')
            ->count();

        // Топ 5 SKU
        $this->topProducts = $this->db->table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.business_group_id', $businessGroupId)
            ->where('orders.created_at', '>=', $since30d)
            ->selectRaw("order_items.product_id, order_items.product_name, SUM(order_items.quantity) as qty, SUM(order_items.price * order_items.quantity) as total")
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(static fn ($r) => (array) $r)
            ->toArray();

        // Последние 10 заказов
        $this->recentOrders = $this->db->table('orders')
            ->where('business_group_id', $businessGroupId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'uuid', 'status', 'total_amount', 'vertical', 'created_at'])
            ->map(static fn ($r) => (array) $r)
            ->toArray();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Обновить')
                ->icon('heroicon-o-arrow-path')
                ->action('refresh'),

            Action::make('new_order')
                ->label('Новый заказ')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->url(static fn () => '/b2b/b2b-orders/create'),
        ];
    }

    public function getWidgets(): array
    {
        return [
            CreditLimitWidget::class,
            B2BOrdersStatsWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 2;
    }
}
