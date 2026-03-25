declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Fitness\Filament\Resources\TrainerResource\Pages;

use App\Domains\Fitness\Filament\Resources\TrainerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final /**
 * ListTrainers
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListTrainers extends ListRecords
{
    protected static string $resource = TrainerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
