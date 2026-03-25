declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Taxi\Filament\Resources\TaxiVehicleResource\Pages;

use App\Domains\Taxi\Filament\Resources\TaxiVehicleResource;
use Filament\Resources\Pages\CreateRecord;

final /**
 * CreateTaxiVehicle
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateTaxiVehicle extends CreateRecord
{
    protected static string $resource = TaxiVehicleResource::class;
}
