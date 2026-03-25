declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\HealthyFoodResource\Pages;

use App\Filament\Tenant\Resources\HealthyFoodResource;
use Filament\Resources\Pages\CreateRecord;

final /**
 * CreateHealthyFood
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateHealthyFood extends CreateRecord
{
    protected static string $resource = HealthyFoodResource::class;
}
