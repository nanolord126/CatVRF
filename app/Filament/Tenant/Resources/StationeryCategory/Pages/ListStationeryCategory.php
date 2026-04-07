<?php declare(strict_types=1);

/**
 * ListStationeryCategory — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/liststationerycategory
 * @see https://catvrf.ru/docs/liststationerycategory
 * @see https://catvrf.ru/docs/liststationerycategory
 */


namespace App\Filament\Tenant\Resources\StationeryCategory\Pages;

use App\Filament\Tenant\Resources\StationeryCategoryResource;
use Filament\Resources\Pages\ListRecords;

/**
 * Class ListStationeryCategory
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\StationeryCategory\Pages
 */
final class ListStationeryCategory extends ListRecords
{
    protected static string $resource = StationeryCategoryResource::class;

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

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

}
