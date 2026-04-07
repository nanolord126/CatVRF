<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\Pages;

use App\Filament\Tenant\Resources\Beauty\BeautyResource;
use Filament\Actions\CreateAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * ListBeauties — Filament Page (Layer 9).
 *
 * Tenant-scoped salon listing with audit logging.
 * No constructor injection — services resolved via app().
 *
 * @package App\Filament\Tenant\Resources\Beauty\Pages
 */
final class ListBeauties extends ListRecords
{
    protected static string $resource = BeautyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Новый салон')
                ->icon('heroicon-m-plus'),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $tenantId      = filament()->getTenant()?->id;
        $userId        = filament()->auth()->id();
        $correlationId = Str::uuid()->toString();

        app(LoggerInterface::class)->info('Beauty ListRecords accessed', [
            'tenant_id'      => $tenantId,
            'user_id'        => $userId,
            'correlation_id' => $correlationId,
        ]);

        return BeautyResource::getEloquentQuery()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->with(['tenant', 'businessGroup', 'masters'])
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

    public function render(): \Illuminate\Contracts\View\View
    {
        app(LoggerInterface::class)->info('ListBeauties page rendered', [
            'user_id'   => filament()->auth()->id(),
            'tenant_id' => filament()->getTenant()?->id,
        ]);

        return parent::render();
    }
}
