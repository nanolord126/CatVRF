declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Filament\Resources\EntertainmentEventResource\Pages;

use App\Domains\Entertainment\Filament\Resources\EntertainmentEventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final /**
 * ListEntertainmentEvents
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListEntertainmentEvents extends ListRecords
{
    protected static string $resource = EntertainmentEventResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
