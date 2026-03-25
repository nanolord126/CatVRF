declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Taxi\Filament\Resources\TaxiDriverResource\Pages;

use App\Domains\Taxi\Filament\Resources\TaxiDriverResource;
use Filament\Resources\Pages\CreateRecord;

final /**
 * CreateTaxiDriver
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateTaxiDriver extends CreateRecord
{
    protected static string $resource = TaxiDriverResource::class;
}
