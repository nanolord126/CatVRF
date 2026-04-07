<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\VehicleResource\Pages;


use Illuminate\Contracts\Auth\Guard;
use App\Filament\Tenant\Resources\VehicleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

/**
 * Class CreateVehicle
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\VehicleResource\Pages
 */
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
            ->performedBy($this->guard->user())
            ->on($this->record)
            ->withProperty('correlation_id', $this->record->correlation_id)
            ->log('Vehicle registered in tenant fleet');
    }

    /**
     * Get the string representation of this object.
     *
     * @return string
     */
    public function __toString(): string
    {
        return static::class . '::' . ($this->id ?? 'new');
    }

    /**
     * Determine if this instance is valid for the current context.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return true;
    }
}
