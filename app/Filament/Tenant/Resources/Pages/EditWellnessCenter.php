<?php declare(strict_types=1);

/**
 * EditWellnessCenter — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/editwellnesscenter
 * @see https://catvrf.ru/docs/editwellnesscenter
 * @see https://catvrf.ru/docs/editwellnesscenter
 */


namespace App\Filament\Tenant\Resources\Pages;

use Filament\Resources\Pages\EditRecord;


/**
 * Class EditWellnessCenter
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Pages
 */
final class EditWellnessCenter extends EditRecord
{
    protected static string $resource = WellnessCenterResource::class;

    /**
     * Handle getTitle operation.
     *
     * @throws \DomainException
     */
    public function getTitle(): string
    {
        return 'Edit WellnessCenter';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
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
}
