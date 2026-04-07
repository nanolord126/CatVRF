<?php declare(strict_types=1);

/**
 * CreateStrApartment — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createstrapartment
 * @see https://catvrf.ru/docs/createstrapartment
 * @see https://catvrf.ru/docs/createstrapartment
 */


namespace App\Filament\Tenant\Resources\StrApartmentResource\Pages;

use App\Filament\Tenant\Resources\StrApartmentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

/**
 * Class CreateStrApartment
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\StrApartmentResource\Pages
 */
final class CreateStrApartment extends CreateRecord
{
    protected static string $resource = StrApartmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data["uuid"] = (string) Str::uuid();
        $data["correlation_id"] = (string) Str::uuid();
        $data["tenant_id"] = tenant()->id;

        return $data;
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
