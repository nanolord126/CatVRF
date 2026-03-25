declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Taxi\Filament\Resources\TaxiDriverResource\Pages;

use App\Domains\Taxi\Filament\Resources\TaxiDriverResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final /**
 * ListTaxiDrivers
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListTaxiDrivers extends ListRecords
{
    protected static string $resource = TaxiDriverResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
