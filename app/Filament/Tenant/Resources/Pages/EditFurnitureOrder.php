<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FurnitureOrder\Pages;

use use App\Filament\Tenant\Resources\FurnitureOrderResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditFurnitureOrder extends EditRecord
{
    protected static string $resource = FurnitureOrderResource::class;

    public function getTitle(): string
    {
        return 'Edit FurnitureOrder';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}