<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FurnitureProduct\Pages;

use use App\Filament\Tenant\Resources\FurnitureProductResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditFurnitureProduct extends EditRecord
{
    protected static string $resource = FurnitureProductResource::class;

    public function getTitle(): string
    {
        return 'Edit FurnitureProduct';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}