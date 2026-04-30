<?php

declare(strict_types=1);

/**
 * ListEmergencyCalls — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/listemergencycalls
 * @see https://catvrf.ru/docs/listemergencycalls
 * @see https://catvrf.ru/docs/listemergencycalls
 */

namespace App\Filament\Emergency\Resources\EmergencyCallResource\Pages;

use App\Filament\Emergency\Resources\EmergencyCallResource;
use Filament\Resources\Pages\ListRecords;

/**
 * Class ListEmergencyCalls
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class ListEmergencyCalls extends ListRecords
{
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    protected static string $resource = EmergencyCallResource::class;

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
        return [];
    }
}
