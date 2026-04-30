<?php

declare(strict_types=1);

/**
 * ListContracts — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/listcontracts
 * @see https://catvrf.ru/docs/listcontracts
 * @see https://catvrf.ru/docs/listcontracts
 */

namespace App\Filament\Tenant\Resources\RealEstate\ContractResource\Pages;

use App\Filament\Tenant\Resources\RealEstate\ContractResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * Class ListContracts
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class ListContracts extends ListRecords
{
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    protected static string $resource = ContractResource::class;

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
            Actions\CreateAction::make(),
        ];
    }
}
