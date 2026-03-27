<?php

declare(strict_types=1);


namespace App\Domains\EventPlanning\Entertainment\Filament\Resources\EntertainmentVenueResource\Pages;

use App\Domains\EventPlanning\Entertainment\Filament\Resources\EntertainmentVenueResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final /**
 * EditEntertainmentVenue
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EditEntertainmentVenue extends EditRecord
{
    protected static string $resource = EntertainmentVenueResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
