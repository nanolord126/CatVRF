<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MedicalClinicResource\Pages;

use App\Filament\Tenant\Resources\MedicalClinicResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

final class CreateRecordMedicalClinic extends CreateRecord
{
    protected static string $resource = MedicalClinicResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = (string)Str::uuid();
        $data['tenant_id'] = tenant()->id;
        $data['correlation_id'] = (string)Str::uuid();
        
        return $data;
    }
}
