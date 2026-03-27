<?php

declare(strict_types=1);


namespace App\Filament\Tenant\Resources\BeautyResource\Pages;

use App\Filament\Tenant\Resources\BeautyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final /**
 * EditBeautySalon
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EditBeautySalon extends EditRecord
{
    protected static string $resource = BeautyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
