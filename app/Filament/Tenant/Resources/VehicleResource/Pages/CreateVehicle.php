<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\VehicleResource\Pages;

use App\Filament\Tenant\Resources\VehicleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

final class CreateVehicle extends CreateRecord
{
    protected static string $resource = VehicleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = (string) Str::uuid();
        $data['tenant_id'] = tenant()->id;
        $data['correlation_id'] = $data['correlation_id'] ?? (string) Str::uuid();

        return $data;
    }

    protected function afterCreate(): void
    {
        activity()
            ->performedBy(auth()->user())
            ->on($this->record)
            ->withProperty('correlation_id', $this->record->correlation_id)
            ->log('Vehicle registered in tenant fleet');
    }
}
