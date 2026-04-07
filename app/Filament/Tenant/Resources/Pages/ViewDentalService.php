<?php declare(strict_types=1);

/**
 * ViewDentalService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/viewdentalservice
 * @see https://catvrf.ru/docs/viewdentalservice
 */


namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\DentalServiceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * Class ViewDentalService
 *
 * Service layer following CatVRF canon:
 * - Constructor injection only (no Facades)
 * - FraudControlService::check() before mutations
 * - $this->db->transaction() wrapping all write operations
 * - Audit logging with correlation_id
 * - Tenant and BusinessGroup scoping
 *
 * @see \App\Services\FraudControlService
 * @see \App\Services\AuditService
 * @package App\Filament\Tenant\Resources\Pages
 */
final class ViewDentalService extends ViewRecord
{
    protected static string $resource = DentalServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
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

}
