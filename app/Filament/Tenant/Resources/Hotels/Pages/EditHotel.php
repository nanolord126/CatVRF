<?php

declare(strict_types=1);


namespace App\Filament\Tenant\Resources\Hotels\Pages;

use App\Filament\Tenant\Resources\Hotels\HotelResource;
use Filament\Resources\Pages\EditRecord;

final /**
 * EditHotel
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EditHotel extends EditRecord
{
    protected static string $resource = HotelResource::class;
}
