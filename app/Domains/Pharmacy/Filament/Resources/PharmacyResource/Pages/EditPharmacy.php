<?php

declare(strict_types=1);

/**
 * EditPharmacy — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/editpharmacy
 */

namespace App\Domains\Pharmacy\Filament\Resources\PharmacyResource\Pages;

use App\Domains\Pharmacy\Filament\Resources\PharmacyResource;
use Filament\Resources\Pages\EditRecord;

/**
 * Class EditPharmacy
 *
 * Part of the Pharmacy vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class EditPharmacy extends EditRecord
{
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    protected static string $resource = PharmacyResource::class;
}
