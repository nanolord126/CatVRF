<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalAppointment\Pages;

use use App\Filament\Tenant\Resources\DentalAppointmentResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditDentalAppointment extends EditRecord
{
    protected static string $resource = DentalAppointmentResource::class;

    public function getTitle(): string
    {
        return 'Edit DentalAppointment';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}