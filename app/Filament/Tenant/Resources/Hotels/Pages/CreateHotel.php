<?php

declare(strict_types=1);


namespace App\Filament\Tenant\Resources\Hotels\Pages;

use App\Filament\Tenant\Resources\Hotels\HotelResource;
use Filament\Resources\Pages\CreateRecord;

final /**
 * CreateHotel
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateHotel extends CreateRecord
{
    protected static string $resource = HotelResource::class;
}
