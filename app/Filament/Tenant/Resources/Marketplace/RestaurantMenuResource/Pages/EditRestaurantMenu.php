<?php

namespace App\Filament\Tenant\Resources\Marketplace\RestaurantMenuResource\Pages;

use App\Filament\Tenant\Resources\Marketplace\RestaurantMenuResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRestaurantMenu extends EditRecord
{
    protected static string $resource = RestaurantMenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
