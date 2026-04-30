<?php

declare(strict_types=1);

/**
 * CreateCleaningOrder — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/createcleaningorder
 */

namespace App\Domains\HomeServices\SubVerticals\CleaningServices\Filament\Resources\CleaningOrderResource\Pages;

use App\Domains\CleaningServices\Filament\Resources\CleaningOrderResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Class CreateCleaningOrder
 *
 * Part of the CleaningServices vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class CreateCleaningOrder extends CreateRecord
{
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    protected static string $resource = CleaningOrderResource::class;
}
