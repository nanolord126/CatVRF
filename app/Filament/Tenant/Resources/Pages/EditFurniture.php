<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Furniture\Pages;

use use App\Filament\Tenant\Resources\FurnitureResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditFurniture extends EditRecord
{
    protected static string $resource = FurnitureResource::class;

    public function getTitle(): string
    {
        return 'Edit Furniture';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}