<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FurnitureResource\Pages;

use App\Filament\Tenant\Resources\FurnitureResource;
use Filament\Resources\Pages\ListRecords;

final class ListFurnitures extends ListRecords
{
    protected static string $resource = FurnitureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
