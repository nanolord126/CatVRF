<?php declare(strict_types=1);

/**
 * EditHouseholdProduct — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/edithouseholdproduct
 */


namespace App\Domains\HouseholdGoods\Filament\Resources\HouseholdProductResource\Pages;

use App\Domains\HouseholdGoods\Filament\Resources\HouseholdProductResource;
use Filament\Resources\Pages\EditRecord;

/**
 * Class EditHouseholdProduct
 *
 * Part of the HouseholdGoods vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Domains\HouseholdGoods\Filament\Resources\HouseholdProductResource\Pages
 */
final class EditHouseholdProduct extends EditRecord
{
    protected static string $resource = HouseholdProductResource::class;
/**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

}