<?php

declare(strict_types=1);

namespace App\Filament\Resources\WarehouseZoneResource\Pages;

use App\Filament\Resources\WarehouseZoneResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListWarehouseZones extends ListRecords
{
    protected static string $resource = WarehouseZoneResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
