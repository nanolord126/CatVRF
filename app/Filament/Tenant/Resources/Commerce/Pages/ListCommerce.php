<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Commerce\Pages;

use App\Filament\Tenant\Resources\Commerce\CommerceResource;
use Filament\Actions\{CreateAction,DeleteBulkAction};
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\{Log,DB};
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

final class ListCommerce extends ListRecords
{
    protected static string $resource = CommerceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Новая запись')
                ->icon('heroicon-m-plus'),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $tenantId = filament()->getTenant()->id;
        $userId = auth()->id();
        $correlationId = Str::uuid()->toString();

        Log::channel('audit')->info('Commerce ListRecords accessed', [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'correlation_id' => $correlationId,
        ]);

        return CommerceResource::getEloquentQuery()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->with(['tenant', 'businessGroup'])
            ->orderBy('created_at', 'desc');
    }

    protected function getTableBulkActions(): array
    {
        return [
            DeleteBulkAction::make()
                ->label('Удалить выбранные')
                ->icon('heroicon-m-trash'),
        ];
    }

    public function render()
    {
        Log::channel('audit')->info('ListCommerce page rendered', [
            'user_id' => auth()->id(),
            'tenant_id' => filament()->getTenant()->id,
        ]);

        return parent::render();
    }
}