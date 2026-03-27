<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\StrApartmentResource\Pages;

use App\Filament\Tenant\Resources\StrApartmentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

final class CreateStrApartment extends CreateRecord
{
    protected static string $resource = StrApartmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data["uuid"] = (string) Str::uuid();
        $data["correlation_id"] = (string) Str::uuid();
        $data["tenant_id"] = tenant()->id;

        return $data;
    }
}
