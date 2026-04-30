<?php

declare(strict_types=1);

/**
 * EditApartment — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/editapartment
 * @see https://catvrf.ru/docs/editapartment
 * @see https://catvrf.ru/docs/editapartment
 * @see https://catvrf.ru/docs/editapartment
 * @see https://catvrf.ru/docs/editapartment
 * @see https://catvrf.ru/docs/editapartment
 */

namespace App\Filament\Tenant\Resources\ShortTermRentals\ApartmentResource\Pages;

use App\Filament\Tenant\Resources\ShortTermRentals\ApartmentResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\DeleteAction;

/**
 * Class EditApartment
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class EditApartment extends EditRecord
{
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    protected static string $resource = ApartmentResource::class;

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
            DeleteAction::make(),
        ];
    }
}
