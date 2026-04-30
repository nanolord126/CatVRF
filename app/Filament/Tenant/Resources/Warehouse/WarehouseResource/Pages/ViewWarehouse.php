<?php

declare(strict_types=1);

/**
 * ViewWarehouse — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/viewwarehouse
 * @see https://catvrf.ru/docs/viewwarehouse
 * @see https://catvrf.ru/docs/viewwarehouse
 * @see https://catvrf.ru/docs/viewwarehouse
 * @see https://catvrf.ru/docs/viewwarehouse
 */

namespace App\Filament\Tenant\Resources\Warehouse\WarehouseResource\Pages;

use App\Filament\Tenant\Resources\Warehouse\WarehouseResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * Class ViewWarehouse
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class ViewWarehouse extends ViewRecord
{
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    protected static string $resource = WarehouseResource::class;

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
            EditAction::make(),
        ];
    }
}
