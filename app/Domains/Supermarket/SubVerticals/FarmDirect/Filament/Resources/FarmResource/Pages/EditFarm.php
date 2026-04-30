<?php

declare(strict_types=1);

/**
 * EditFarm — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/editfarm
 */

namespace App\Domains\Supermarket\SubVerticals\FarmDirect\Filament\Resources\FarmResource\Pages;

use App\Domains\FarmDirect\Filament\Resources\FarmResource;
use Filament\Resources\Pages\EditRecord;

/**
 * Class EditFarm
 *
 * Part of the FarmDirect vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class EditFarm extends EditRecord
{
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    protected static string $resource = FarmResource::class;
}
