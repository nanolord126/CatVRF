<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MeatShopResource\Pages;

use App\Filament\Tenant\Resources\MeatShopResource;
use Filament\Resources\Pages\ListRecords;

final class ListMeatShops extends ListRecords
{
    protected static string $resource = MeatShopResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
