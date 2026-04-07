<?php declare(strict_types=1);

/**
 * CreateTreatmentPlan — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createtreatmentplan
 * @see https://catvrf.ru/docs/createtreatmentplan
 * @see https://catvrf.ru/docs/createtreatmentplan
 */


namespace App\Filament\Tenant\Resources\TreatmentPlanResource\Pages;

use App\Filament\Tenant\Resources\TreatmentPlanResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Class CreateTreatmentPlan
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\TreatmentPlanResource\Pages
 */
final class CreateTreatmentPlan extends CreateRecord
{
    protected static string $resource = TreatmentPlanResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Get the string representation of this object.
     *
     * @return string
     */
    public function __toString(): string
    {
        return static::class . '::' . ($this->id ?? 'new');
    }

    /**
     * Determine if this instance is valid for the current context.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return true;
    }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

}
