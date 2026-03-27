<?php

declare(strict_types=1);


namespace App\Filament\Tenant\Resources\Jewelry\JewelryItemResource\Pages;

use App\Filament\Tenant\Resources\Jewelry\JewelryItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final /**
 * EditJewelryItem
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EditJewelryItem extends EditRecord
{
    protected static string $resource = JewelryItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
