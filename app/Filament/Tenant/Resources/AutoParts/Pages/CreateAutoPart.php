declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\AutoParts\Pages;

use App\Filament\Tenant\Resources\AutoParts\AutoPartResource;
use Filament\Resources\Pages\CreateRecord;

final /**
 * CreateAutoPart
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateAutoPart extends CreateRecord
{
    protected static string $resource = AutoPartResource::class;
}
