declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Fitness\Filament\Resources\FitnessClassResource\Pages;

use App\Domains\Fitness\Filament\Resources\FitnessClassResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final /**
 * ListFitnessClasses
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListFitnessClasses extends ListRecords
{
    protected static string $resource = FitnessClassResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
