declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Filament\Resources\EntertainmentVenueResource\Pages;

use App\Domains\Entertainment\Filament\Resources\EntertainmentVenueResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final /**
 * CreateEntertainmentVenue
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateEntertainmentVenue extends CreateRecord
{
    protected static string $resource = EntertainmentVenueResource::class;
}
