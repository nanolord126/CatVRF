<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Grocery\GroceryStoreResource\Pages;

use App\Filament\Tenant\Resources\Grocery\GroceryStoreResource;
use Filament\Resources\Pages\EditRecord;

final class EditGroceryStore extends EditRecord
{
    protected static string $resource = GroceryStoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
