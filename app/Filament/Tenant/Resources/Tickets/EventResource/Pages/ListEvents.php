declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Tickets\EventResource\Pages;

use App\Filament\Tenant\Resources\Tickets\EventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final /**
 * ListEvents
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
