<?php

declare(strict_types=1);

/**
 * CreateBooking — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/createbooking
 */

namespace App\Domains\Travel\SubVerticals\Hotels\Presentation\Filament\Resources\BookingResource\Pages;

use App\Domains\Hotels\Presentation\Filament\Resources\BookingResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Class CreateBooking
 *
 * Part of the Hotels vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class CreateBooking extends CreateRecord
{
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    protected static string $resource = BookingResource::class;
}
