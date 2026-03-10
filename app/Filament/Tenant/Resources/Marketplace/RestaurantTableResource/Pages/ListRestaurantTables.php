<?php

namespace App\Filament\Tenant\Resources\Marketplace\RestaurantTableResource\Pages;

use App\Filament\Tenant\Resources\Marketplace\RestaurantTableResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRestaurantTables extends ListRecords
{
    protected static string $resource = RestaurantTableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
