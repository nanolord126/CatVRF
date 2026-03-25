declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Fitness\Filament\Resources\FitnessClassResource\Pages;

use App\Domains\Fitness\Filament\Resources\FitnessClassResource;
use Filament\Resources\Pages\CreateRecord;

final /**
 * CreateFitnessClass
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateFitnessClass extends CreateRecord
{
    protected static string $resource = FitnessClassResource::class;
}
