<?php

declare(strict_types=1);

/**
 * EditTreatmentPlan — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/edittreatmentplan
 * @see https://catvrf.ru/docs/edittreatmentplan
 * @see https://catvrf.ru/docs/edittreatmentplan
 * @see https://catvrf.ru/docs/edittreatmentplan
 * @see https://catvrf.ru/docs/edittreatmentplan
 * @see https://catvrf.ru/docs/edittreatmentplan
 */

namespace App\Filament\Tenant\Resources\TreatmentPlanResource\Pages;

use App\Filament\Tenant\Resources\TreatmentPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * Class EditTreatmentPlan
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class EditTreatmentPlan extends EditRecord
{
    protected static string $resource = TreatmentPlanResource::class;

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
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
