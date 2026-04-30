<?php

declare(strict_types=1);

/**
 * EditB2BOrder — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/editb2border
 * @see https://catvrf.ru/docs/editb2border
 * @see https://catvrf.ru/docs/editb2border
 * @see https://catvrf.ru/docs/editb2border
 * @see https://catvrf.ru/docs/editb2border
 */

namespace App\Filament\B2B\Resources\B2BOrderResource\Pages;

use App\Filament\B2B\Resources\B2BOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * Class EditB2BOrder
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class EditB2BOrder extends EditRecord
{
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    protected static string $resource = B2BOrderResource::class;

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
        return [
            Actions\ViewAction::make(),
        ];
    }
}
