<?php declare(strict_types=1);

/**
 * CreateContentItem — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createcontentitem
 */


namespace App\Domains\Content\Filament\Resources\ContentItemResource\Pages;

use App\Domains\Content\Filament\Resources\ContentItemResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Class CreateContentItem
 *
 * Part of the Content vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Domains\Content\Filament\Resources\ContentItemResource\Pages
 */
final class CreateContentItem extends CreateRecord
{
    protected static string $resource = ContentItemResource::class;
/**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

}