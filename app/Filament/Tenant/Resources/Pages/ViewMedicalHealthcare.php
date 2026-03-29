<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MedicalHealthcare\Pages;

use use App\Filament\Tenant\Resources\MedicalHealthcareResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewMedicalHealthcare extends ViewRecord
{
    protected static string $resource = MedicalHealthcareResource::class;

    public function getTitle(): string
    {
        return 'View MedicalHealthcare';
    }
}