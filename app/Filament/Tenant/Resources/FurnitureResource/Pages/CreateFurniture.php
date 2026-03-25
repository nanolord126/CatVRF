declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FurnitureResource\Pages;

use App\Filament\Tenant\Resources\FurnitureResource;
use Filament\Resources\Pages\CreateRecord;

final /**
 * CreateFurniture
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateFurniture extends CreateRecord
{
    protected static string $resource = FurnitureResource::class;
}
