<?php

declare(strict_types=1);


namespace App\Filament\Tenant\Resources\MedicalSupplies\Pages;

use App\Filament\Tenant\Resources\MedicalSupplies\MedicalSupplyResource;
use Filament\Resources\Pages\CreateRecord;

final /**
 * CreateMedicalSupply
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateMedicalSupply extends CreateRecord
{
    protected static string $resource = MedicalSupplyResource::class;
}
