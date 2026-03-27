<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\VehicleResource\Pages;

use App\Filament\Tenant\Resources\VehicleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

final class ListVehicles extends ListRecords
{
    protected static string $resource = VehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Добавить автомобиль')
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
