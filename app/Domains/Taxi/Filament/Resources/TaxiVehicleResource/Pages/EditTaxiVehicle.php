<?php

declare(strict_types=1);


namespace App\Domains\Taxi\Filament\Resources\TaxiVehicleResource\Pages;

use App\Domains\Taxi\Filament\Resources\TaxiVehicleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final /**
 * EditTaxiVehicle
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EditTaxiVehicle extends EditRecord
{
    protected static string $resource = TaxiVehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
