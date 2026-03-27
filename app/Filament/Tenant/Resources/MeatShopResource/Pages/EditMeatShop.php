<?php

declare(strict_types=1);


namespace App\Filament\Tenant\Resources\MeatShopResource\Pages;

use App\Filament\Tenant\Resources\MeatShopResource;
use Filament\Resources\Pages\EditRecord;

final /**
 * EditMeatShop
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EditMeatShop extends EditRecord
{
    protected static string $resource = MeatShopResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
