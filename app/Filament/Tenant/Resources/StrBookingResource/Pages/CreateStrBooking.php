<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\StrBookingResource\Pages;

use App\Filament\Tenant\Resources\StrBookingResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

final class CreateStrBooking extends CreateRecord
{
    protected static string $resource = StrBookingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data["uuid"] = (string) Str::uuid();
        $data["correlation_id"] = (string) Str::uuid();
        $data["tenant_id"] = tenant()->id;

        return $data;
    }
}
