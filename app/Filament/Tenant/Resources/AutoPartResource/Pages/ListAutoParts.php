<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\AutoPartResource\Pages;

use App\Filament\Tenant\Resources\AutoPartResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

final class ListAutoParts extends ListRecords
{
    protected static string $resource = AutoPartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Добавить запчасть')
                ->icon('heroicon-o-plus'),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->where('tenant_id', tenant()->id);
    }
}
