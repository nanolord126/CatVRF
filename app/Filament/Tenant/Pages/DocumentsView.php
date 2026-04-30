<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Database\DatabaseManager;

/**
 * DocumentsView — управление документами в Tenant Panel.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Функционал: загрузка, хранение, просмотр документов (договоры, акты, счёта).
 * Tenant-scoped: все документы фильтруются по tenant_id.
 */
final class DocumentsView extends Page
{
    public array $documents = [];
    public array $stats = [];
    public string $filter = 'all';
    public string $search = '';

    protected static ?string $navigationIcon = 'heroicon-o-document';
    protected static ?string $navigationLabel = 'Документы';
    protected static ?string $navigationGroup = 'Business';
    protected static ?string $slug = 'documents';
    protected static ?int $navigationSort = 9;
    protected static string $view = 'filament.tenant.pages.documents-view';

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
            Action::make('upload')
                ->label('Загрузить')
                ->icon('heroicon-o-arrow-up-tray')
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

        $query = $this->db->table('documents')
            ->where('tenant_id', $tenantId);

        if ($this->filter === 'contracts') {
            $query->where('type', 'contract');
        } elseif ($this->filter === 'invoices') {
            $query->where('type', 'invoice');
        } elseif ($this->filter === 'acts') {
            $query->where('type', 'act');
        }

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        $this->documents = $query
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();

        $this->stats = [
            'total_documents' => $this->db->table('documents')
                ->where('tenant_id', $tenantId)
                ->count(),
            'total_size' => (float) $this->db->table('documents')
                ->where('tenant_id', $tenantId)
                ->sum('size') / 1024 / 1024, // MB
            'contracts' => $this->db->table('documents')
                ->where('tenant_id', $tenantId)
                ->where('type', 'contract')
                ->count(),
            'invoices' => $this->db->table('documents')
                ->where('tenant_id', $tenantId)
                ->where('type', 'invoice')
                ->count(),
        ];
    }
}
