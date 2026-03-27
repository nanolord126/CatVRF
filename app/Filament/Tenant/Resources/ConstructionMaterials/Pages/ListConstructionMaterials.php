<?php

declare(strict_types=1);


namespace App\Filament\Tenant\Resources\ConstructionMaterials\Pages;

use App\Filament\Tenant\Resources\ConstructionMaterials\ConstructionMaterialResource;
use Filament\Resources\Pages\ListRecords;

final /**
 * ListConstructionMaterials
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListConstructionMaterials extends ListRecords
{
    protected static string $resource = ConstructionMaterialResource::class;
}
