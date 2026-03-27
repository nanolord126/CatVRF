<?php

declare(strict_types=1);


namespace App\Filament\Tenant\Resources\Grocery\GroceryStoreResource\Pages;

use App\Filament\Tenant\Resources\Grocery\GroceryStoreResource;
use Filament\Resources\Pages\CreateRecord;

final /**
 * CreateGroceryStore
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateGroceryStore extends CreateRecord
{
    protected static string $resource = GroceryStoreResource::class;
}
