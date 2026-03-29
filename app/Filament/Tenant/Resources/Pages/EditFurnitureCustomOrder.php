<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FurnitureCustomOrder\Pages;

use use App\Filament\Tenant\Resources\FurnitureCustomOrderResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditFurnitureCustomOrder extends EditRecord
{
    protected static string $resource = FurnitureCustomOrderResource::class;

    public function getTitle(): string
    {
        return 'Edit FurnitureCustomOrder';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}