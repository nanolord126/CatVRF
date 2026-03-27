<?php

declare(strict_types=1);


namespace App\Filament\Tenant\Resources\Jewelry\JewelryItemResource\Pages;

use App\Filament\Tenant\Resources\Jewelry\JewelryItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final /**
 * ListJewelryItems
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListJewelryItems extends ListRecords
{
    protected static string $resource = JewelryItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
