<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\AutoRepairOrderResource\Pages;

use App\Filament\Tenant\Resources\AutoRepairOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

final class ListAutoRepairOrders extends ListRecords
{
    protected static string $resource = AutoRepairOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Открыть заказ-наряд')
                ->icon('heroicon-o-plus'),
        ];
    }

    /**
     * Tenant scoping.
     */
    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->where('tenant_id', tenant()->id);
    }
}
