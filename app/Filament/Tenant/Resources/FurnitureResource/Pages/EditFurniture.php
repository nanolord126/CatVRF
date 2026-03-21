<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FurnitureResource\Pages;

use App\Filament\Tenant\Resources\FurnitureResource;
use Filament\Resources\Pages\EditRecord;

final class EditFurniture extends EditRecord
{
    protected static string $resource = FurnitureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
