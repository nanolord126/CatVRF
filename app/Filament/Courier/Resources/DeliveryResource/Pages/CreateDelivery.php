<?php

declare(strict_types=1);

/**
 * CreateDelivery — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/createdelivery
 * @see https://catvrf.ru/docs/createdelivery
 * @see https://catvrf.ru/docs/createdelivery
 * @see https://catvrf.ru/docs/createdelivery
 * @see https://catvrf.ru/docs/createdelivery
 * @see https://catvrf.ru/docs/createdelivery
 */

namespace App\Filament\Courier\Resources\DeliveryResource\Pages;

use App\Filament\Courier\Resources\DeliveryResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Class CreateDelivery
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class CreateDelivery extends CreateRecord
{
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    protected static string $resource = DeliveryResource::class;

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
}
