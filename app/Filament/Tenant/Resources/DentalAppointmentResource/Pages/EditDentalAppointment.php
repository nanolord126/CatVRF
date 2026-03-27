<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalAppointmentResource\Pages;

use App\Filament\Tenant\Resources\DentalAppointmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditDentalAppointment extends EditRecord
{
    protected static string $resource = DentalAppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
