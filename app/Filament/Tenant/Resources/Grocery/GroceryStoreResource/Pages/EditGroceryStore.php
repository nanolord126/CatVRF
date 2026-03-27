<?php

declare(strict_types=1);


namespace App\Filament\Tenant\Resources\Grocery\GroceryStoreResource\Pages;

use App\Filament\Tenant\Resources\Grocery\GroceryStoreResource;
use Filament\Resources\Pages\EditRecord;

final /**
 * EditGroceryStore
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EditGroceryStore extends EditRecord
{
    protected static string $resource = GroceryStoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
