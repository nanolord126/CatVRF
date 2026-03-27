<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\AutoRepairOrderResource\Pages;

use App\Filament\Tenant\Resources\AutoRepairOrderResource;
use App\Domains\Auto\Models\Vehicle;
use Illuminate\Support\Str;
use Filament\Resources\Pages\CreateRecord;

final class CreateAutoRepairOrder extends CreateRecord
{
    protected static string $resource = AutoRepairOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = (string) Str::uuid();
        $data['tenant_id'] = tenant()->id;
        $data['correlation_id'] = $data['correlation_id'] ?? (string) Str::uuid();
        $data['status'] = 'pending';

        return $data;
    }

    protected function afterCreate(): void
    {
        // Перевод авто в статус ремонта при создании заказа
        $vehicle = Vehicle::find($this->getRecord()->vehicle_id);
        if ($vehicle) {
            $vehicle->update(['status' => 'repair']);
        }

        activity()
            ->performedBy(auth()->user())
            ->on($this->getRecord())
            ->withProperty('correlation_id', $this->getRecord()->correlation_id)
            ->withProperty('vehicle_uuid', $vehicle->uuid ?? 'N/A')
            ->log('Auto repair order opened');
    }
}
