declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\HealthyFoodResource\Pages;

use App\Filament\Tenant\Resources\HealthyFoodResource;
use Filament\Resources\Pages\ListRecords;

final /**
 * ListHealthyFoods
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListHealthyFoods extends ListRecords
{
    protected static string $resource = HealthyFoodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
