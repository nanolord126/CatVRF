declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Fitness\Filament\Resources\GymResource\Pages;

use App\Domains\Fitness\Filament\Resources\GymResource;
use Filament\Resources\Pages\EditRecord;

final /**
 * EditGym
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EditGym extends EditRecord
{
    protected static string $resource = GymResource::class;
}
