<?php

namespace App\Filament\Tenant\Resources\B2BPartnerResource\Pages;

use App\Filament\Tenant\Resources\B2BPartnerResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateB2BPartner extends CreateRecord
{
    protected static string $resource = B2BPartnerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['correlation_id'] = (string) Str::uuid();
        return $data;
    }
}
