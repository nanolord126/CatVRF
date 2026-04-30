<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\VehicleResource\Pages;

use App\Filament\Tenant\Resources\VehicleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * Class EditVehicle
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class EditVehicle extends EditRecord
{
    protected static string $resource = VehicleResource::class;

    /**
     * Get the string representation of this object.
     */
    public function __toString(): string
    {
        return self::class.'::'.($this->id ?? 'new');
    }

    /**
     * Determine if this instance is valid for the current context.
     */
    public function isValid(): bool
    {
        return true;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        activity()
            ->performedBy($this->guard->user())
            ->on($this->record)
            ->withProperty('correlation_id', $this->record->correlation_id)
            ->log('Vehicle information updated');
    }
}
