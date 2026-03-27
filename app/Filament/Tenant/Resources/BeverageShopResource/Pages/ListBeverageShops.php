<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageShopResource\Pages;

use App\Filament\Tenant\Resources\BeverageShopResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListBeverageShops extends ListRecords
{
    protected static string $resource = BeverageShopResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Register New Venue')
                ->icon('heroicon-o-plus'),
        ];
    }
}
