declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\CarSales\Filament\Resources\CarDealerStorefrontResource\Pages;

use App\Domains\CarSales\Filament\Resources\CarDealerStorefrontResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final /**
 * ListCarDealerStorefronts
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListCarDealerStorefronts extends ListRecords
{
    protected static string $resource = CarDealerStorefrontResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
