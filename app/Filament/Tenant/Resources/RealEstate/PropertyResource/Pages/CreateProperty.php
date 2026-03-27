<?php

declare(strict_types=1);


namespace App\Filament\Tenant\Resources\RealEstate\PropertyResource\Pages;

use App\Filament\Tenant\Resources\RealEstate\PropertyResource;
use Filament\Resources\Pages\CreateRecord;

final /**
 * CreateProperty
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateProperty extends CreateRecord
{
    protected static string $resource = PropertyResource::class;
}
