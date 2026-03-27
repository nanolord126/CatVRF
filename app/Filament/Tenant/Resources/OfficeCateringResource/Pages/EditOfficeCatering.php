<?php

declare(strict_types=1);


namespace App\Filament\Tenant\Resources\OfficeCateringResource\Pages;

use App\Filament\Tenant\Resources\OfficeCateringResource;
use Filament\Resources\Pages\EditRecord;

final /**
 * EditOfficeCatering
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EditOfficeCatering extends EditRecord
{
    protected static string $resource = OfficeCateringResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
