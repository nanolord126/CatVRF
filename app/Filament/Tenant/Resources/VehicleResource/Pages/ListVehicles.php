<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\VehicleResource\Pages;

use App\Filament\Tenant\Resources\VehicleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class ListVehicles
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\VehicleResource\Pages
 */
final class ListVehicles extends ListRecords
{
    protected static string $resource = VehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Добавить автомобиль')
                ->icon('heroicon-o-plus'),
        ];
    }

    /**
     * Tenant scoping.
     */
    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->where('tenant_id', tenant()->id);
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
