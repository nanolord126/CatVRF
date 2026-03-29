<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MedicalDoctor\Pages;

use use App\Filament\Tenant\Resources\MedicalDoctorResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateMedicalDoctor extends CreateRecord
{
    protected static string $resource = MedicalDoctorResource::class;

    public function getTitle(): string
    {
        return 'Create MedicalDoctor';
    }
}