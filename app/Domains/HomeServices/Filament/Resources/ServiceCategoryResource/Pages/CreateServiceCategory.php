declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Filament\Resources\ServiceCategoryResource\Pages;

use App\Domains\HomeServices\Filament\Resources\ServiceCategoryResource;
use Filament\Resources\Pages\CreateRecord;

final /**
 * CreateServiceCategory
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateServiceCategory extends CreateRecord
{
    protected static string $resource = ServiceCategoryResource::class;
}
