<?php

namespace App\Filament\Tenant\Resources\Marketplace\RestaurantMenuResource\Pages;

use App\Filament\Tenant\Resources\Marketplace\RestaurantMenuResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRestaurantMenu extends ListRecords
{
    protected static string $resource = RestaurantMenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
