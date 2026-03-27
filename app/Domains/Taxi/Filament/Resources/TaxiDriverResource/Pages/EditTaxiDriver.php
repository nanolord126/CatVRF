<?php

declare(strict_types=1);


namespace App\Domains\Taxi\Filament\Resources\TaxiDriverResource\Pages;

use App\Domains\Taxi\Filament\Resources\TaxiDriverResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final /**
 * EditTaxiDriver
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EditTaxiDriver extends EditRecord
{
    protected static string $resource = TaxiDriverResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
