<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MedicalAppointment\Pages;

use use App\Filament\Tenant\Resources\MedicalAppointmentResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewMedicalAppointment extends ViewRecord
{
    protected static string $resource = MedicalAppointmentResource::class;

    public function getTitle(): string
    {
        return 'View MedicalAppointment';
    }
}