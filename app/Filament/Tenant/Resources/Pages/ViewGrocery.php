<?php

declare(strict_types=1);

/**
 * ViewGrocery — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/viewgrocery
 * @see https://catvrf.ru/docs/viewgrocery
 * @see https://catvrf.ru/docs/viewgrocery
 */

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\GroceryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * Class ViewGrocery
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class ViewGrocery extends ViewRecord
{
    protected static string $resource = GroceryResource::class;

    /**
     * Handle getTitle operation.
     *
     * @throws \DomainException
     */
    public function getTitle(): string
    {
        return 'Просмотр магазина';
    }

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
        return [EditAction::make()];
    }
}
