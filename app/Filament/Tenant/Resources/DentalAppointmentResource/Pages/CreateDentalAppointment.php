<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalAppointmentResource\Pages;

use App\Filament\Tenant\Resources\DentalAppointmentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateDentalAppointment extends CreateRecord
{
    protected static string $resource = DentalAppointmentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
