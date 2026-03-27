<?php

declare(strict_types=1);


namespace App\Domains\Food\Grocery\Filament\Resources\GroceryStoreResource\Pages;

use App\Domains\Food\Grocery\Filament\Resources\GroceryStoreResource;
use Filament\Resources\Pages\ListRecords;

final /**
 * ListGroceryStores
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListGroceryStores extends ListRecords
{
    protected static string $resource = GroceryStoreResource::class;

    protected function getHeaderActions(): array
    {
        return [\Filament\Actions\CreateAction::make()];
    }
}
