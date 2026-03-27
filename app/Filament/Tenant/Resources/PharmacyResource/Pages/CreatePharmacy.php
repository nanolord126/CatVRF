<?php

declare(strict_types=1);


namespace App\Filament\Tenant\Resources\PharmacyResource\Pages;

use App\Filament\Tenant\Resources\PharmacyResource;
use Filament\Resources\Pages\CreateRecord;

final /**
 * CreatePharmacy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreatePharmacy extends CreateRecord
{
    protected static string $resource = PharmacyResource::class;
}
