<?php declare(strict_types=1);

/**
 * ListReferrals — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listreferrals
 */


namespace App\Domains\Referral\Filament\Resources\ReferralResource\Pages;

use App\Domains\Referral\Filament\Resources\ReferralResource;
use Filament\Resources\Pages\ListRecords;

/**
 * Class ListReferrals
 *
 * Part of the Referral vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Domains\Referral\Filament\Resources\ReferralResource\Pages
 */
final class ListReferrals extends ListRecords
{
    protected static string $resource = ReferralResource::class;
/**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

}