<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MedicalClinicResource\Pages;

use App\Filament\Tenant\Resources\MedicalClinicResource;
use Filament\Resources\Pages\EditRecord;

final class EditMedicalClinic extends EditRecord
{
    protected static string $resource = MedicalClinicResource::class;
}
