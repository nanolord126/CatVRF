<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Appointment\Pages;

use use App\Filament\Tenant\Resources\AppointmentResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditAppointment extends EditRecord
{
    protected static string $resource = AppointmentResource::class;

    public function getTitle(): string
    {
        return 'Edit Appointment';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}