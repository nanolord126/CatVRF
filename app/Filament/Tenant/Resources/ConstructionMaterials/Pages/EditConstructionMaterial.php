<?php

declare(strict_types=1);


namespace App\Filament\Tenant\Resources\ConstructionMaterials\Pages;

use App\Filament\Tenant\Resources\ConstructionMaterials\ConstructionMaterialResource;
use Filament\Resources\Pages\EditRecord;

final /**
 * EditConstructionMaterial
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EditConstructionMaterial extends EditRecord
{
    protected static string $resource = ConstructionMaterialResource::class;
}
