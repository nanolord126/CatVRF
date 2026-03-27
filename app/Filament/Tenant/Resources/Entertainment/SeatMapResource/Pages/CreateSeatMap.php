<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment\SeatMapResource\Pages;

use App\Filament\Tenant\Resources\Entertainment\SeatMapResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

final class CreateSeatMap extends CreateRecord
{
    protected static string $resource = SeatMapResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = filament()->getTenant()->id;
        $data['uuid'] = (string) Str::uuid();
        $data['correlation_id'] = (string) Str::uuid();
        return $data;
    }
}
