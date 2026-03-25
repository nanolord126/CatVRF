declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ConstructionMaterials\Pages;

use App\Filament\Tenant\Resources\ConstructionMaterials\ConstructionMaterialResource;
use Filament\Resources\Pages\CreateRecord;

final /**
 * CreateConstructionMaterial
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateConstructionMaterial extends CreateRecord
{
    protected static string $resource = ConstructionMaterialResource::class;
}
