<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Database\DatabaseManager;

/**
 * MarketingView — управление маркетингом в Tenant Panel.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Функционал: кампании, промокоды, analytics, ROI.
 * Tenant-scoped: все данные фильтруются по tenant_id.
 */
final class MarketingView extends Page
{
    public array $campaigns = [];
    public array $stats = [];
    public string $filter = 'active';

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationLabel = 'Маркетинг';
    protected static ?string $navigationGroup = 'Business';
    protected static ?string $slug = 'marketing';
    protected static ?int $navigationSort = 4;
    protected static string $view = 'filament.tenant.pages.marketing-view';

    public function __construct(
        private readonly DatabaseManager $db,
    ) {}

    public function mount(): void
    {
        $this->loadData();
    }

    public function refresh(): void
    {
        $this->loadData();
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->loadData();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_campaign')
                ->label('Создать кампанию')
                ->icon('heroicon-o-plus')
                ->url('#'),
            Action::make('create_promo')
                ->label('Создать промокод')
                ->icon('heroicon-o-ticket')
                ->url('#'),
            Action::make('refresh')
                ->label('Обновить')
                ->icon('heroicon-o-arrow-path')
                ->action('refresh'),
        ];
    }

    private function loadData(): void
    {
        $tenantId = tenant()?->id;
        if (!$tenantId) {
            return;
        }

        $query = $this->db->table('marketing_campaigns')
            ->where('tenant_id', $tenantId);

        if ($this->filter === 'active') {
            $query->where('status', 'active');
        } elseif ($this->filter === 'completed') {
            $query->where('status', 'completed');
        }

        $this->campaigns = $query
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();

        $this->stats = [
            'active_campaigns' => $this->db->table('marketing_campaigns')
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->count(),
            'total_spend' => (float) $this->db->table('marketing_campaigns')
                ->where('tenant_id', $tenantId)
                ->sum('budget') / 100,
            'active_promos' => $this->db->table('promo_codes')
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->count(),
            'conversions' => $this->db->table('marketing_conversions')
                ->where('tenant_id', $tenantId)
                ->where('created_at', '>=', now()->subDays(30))
                ->count(),
        ];
    }
}
