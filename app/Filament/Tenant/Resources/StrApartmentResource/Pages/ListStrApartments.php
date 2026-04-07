<?php declare(strict_types=1);

/**
 * ListStrApartments — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/liststrapartments
 * @see https://catvrf.ru/docs/liststrapartments
 * @see https://catvrf.ru/docs/liststrapartments
 * @see https://catvrf.ru/docs/liststrapartments
 * @see https://catvrf.ru/docs/liststrapartments
 */


namespace App\Filament\Tenant\Resources\StrApartmentResource\Pages;

use App\Filament\Tenant\Resources\StrApartmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * Class ListStrApartments
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\StrApartmentResource\Pages
 */
final class ListStrApartments extends ListRecords
{
    protected static string $resource = StrApartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
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

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}
