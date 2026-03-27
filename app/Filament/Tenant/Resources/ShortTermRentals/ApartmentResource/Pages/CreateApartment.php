<?php

declare(strict_types=1);


namespace App\Filament\Tenant\Resources\ShortTermRentals\ApartmentResource\Pages;

use App\Filament\Tenant\Resources\ShortTermRentals\ApartmentResource;
use Filament\Resources\Pages\CreateRecord;

final /**
 * CreateApartment
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateApartment extends CreateRecord
{
    protected static string $resource = ApartmentResource::class;
}
