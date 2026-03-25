declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\AppointmentResource\Pages;

use App\Filament\Tenant\Resources\AppointmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

final /**
 * ViewAppointment
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ViewAppointment extends ViewRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
