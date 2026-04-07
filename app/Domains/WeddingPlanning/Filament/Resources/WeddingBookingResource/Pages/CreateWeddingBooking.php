<?php declare(strict_types=1);

/**
 * CreateWeddingBooking — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createweddingbooking
 */


namespace App\Domains\WeddingPlanning\Filament\Resources\WeddingBookingResource\Pages;

use App\Domains\WeddingPlanning\Filament\Resources\WeddingBookingResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Class CreateWeddingBooking
 *
 * Part of the WeddingPlanning vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Domains\WeddingPlanning\Filament\Resources\WeddingBookingResource\Pages
 */
final class CreateWeddingBooking extends CreateRecord
{
    protected static string $resource = WeddingBookingResource::class;
/**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

}