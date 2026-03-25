declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Tickets\EventResource\Pages;

use App\Filament\Tenant\Resources\Tickets\EventResource;
use Filament\Resources\Pages\CreateRecord;

final /**
 * CreateEvent
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;
}
