declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Fitness\Filament\Resources\GymResource\Pages;

use App\Domains\Fitness\Filament\Resources\GymResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final /**
 * ListGyms
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListGyms extends ListRecords
{
    protected static string $resource = GymResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
