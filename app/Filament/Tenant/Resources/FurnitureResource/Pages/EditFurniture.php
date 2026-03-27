<?php

declare(strict_types=1);


namespace App\Filament\Tenant\Resources\FurnitureResource\Pages;

use App\Filament\Tenant\Resources\FurnitureResource;
use Filament\Resources\Pages\EditRecord;

final /**
 * EditFurniture
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EditFurniture extends EditRecord
{
    protected static string $resource = FurnitureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
