<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MedicalClinic\Pages;

use use App\Filament\Tenant\Resources\MedicalClinicResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateMedicalClinic extends CreateRecord
{
    protected static string $resource = MedicalClinicResource::class;

    public function getTitle(): string
    {
        return 'Create MedicalClinic';
    }
}