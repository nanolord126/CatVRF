<?php

declare(strict_types=1);

namespace Filament\Resources\ResortZoneResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\ResortZoneResource;

class ListResortZones extends ListRecords
{
    protected static string $resource = ResortZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
