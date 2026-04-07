<?php

declare(strict_types=1);

namespace App\Domains\GeoLogistics\Filament\Resources\LogisticsRouteResource\Pages;

use App\Domains\GeoLogistics\Filament\Resources\LogisticsRouteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListLogisticsRoutes extends ListRecords
{
    protected static string $resource = LogisticsRouteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
