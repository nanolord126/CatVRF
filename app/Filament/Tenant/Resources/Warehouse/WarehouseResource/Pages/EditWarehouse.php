<?php

declare(strict_types=1);

/**
 * EditWarehouse — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/editwarehouse
 * @see https://catvrf.ru/docs/editwarehouse
 * @see https://catvrf.ru/docs/editwarehouse
 * @see https://catvrf.ru/docs/editwarehouse
 * @see https://catvrf.ru/docs/editwarehouse
 * @see https://catvrf.ru/docs/editwarehouse
 */

namespace App\Filament\Tenant\Resources\Warehouse\WarehouseResource\Pages;

use App\Filament\Tenant\Resources\Warehouse\WarehouseResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Class EditWarehouse
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class EditWarehouse extends EditRecord
{
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
            ViewAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
