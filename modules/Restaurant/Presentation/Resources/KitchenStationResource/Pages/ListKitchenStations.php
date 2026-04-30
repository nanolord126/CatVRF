<?php

declare(strict_types=1);

namespace Modules\Restaurant\Presentation\Resources\KitchenStationResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Restaurant\Presentation\Resources\KitchenStationResource;

final class ListKitchenStations extends ListRecords
{
    protected static string $resource = KitchenStationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Pages\Actions\CreateAction::make(),
        ];
    }
}
