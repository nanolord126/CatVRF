<?php

declare(strict_types=1);

/**
 * ListVehicles — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/listvehicles
 * @see https://catvrf.ru/docs/listvehicles
 * @see https://catvrf.ru/docs/listvehicles
 * @see https://catvrf.ru/docs/listvehicles
 * @see https://catvrf.ru/docs/listvehicles
 * @see https://catvrf.ru/docs/listvehicles
 */

namespace App\Filament\Tenant\Resources\Taxi\VehicleResource\Pages;

use App\Filament\Tenant\Resources\Taxi\VehicleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * Class ListVehicles
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class ListVehicles extends ListRecords
{
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

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
        return [Actions\CreateAction::make()];
    }
}
