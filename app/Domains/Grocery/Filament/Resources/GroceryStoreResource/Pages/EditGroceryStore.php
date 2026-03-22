<?php declare(strict_types=1);

namespace App\Domains\Grocery\Filament\Resources\GroceryStoreResource\Pages;

use App\Domains\Grocery\Filament\Resources\GroceryStoreResource;
use Filament\Resources\Pages\EditRecord;

final class EditGroceryStore extends EditRecord
{
    protected static string $resource = GroceryStoreResource::class;

    protected function getHeaderActions(): array
    {
        return [\Filament\Actions\DeleteAction::make()];
    }
}
