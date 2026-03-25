declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Filament\Resources\BookingResource\Pages;

use App\Domains\Entertainment\Filament\Resources\BookingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final /**
 * CreateBooking
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;
}
