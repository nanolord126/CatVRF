<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\AppointmentResource\Pages;

use App\Filament\Tenant\Resources\Beauty\AppointmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

final class ViewAppointment extends ViewRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
