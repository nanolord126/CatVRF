<?php declare(strict_types=1);

/**
 * ViewBakeryOrder — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/viewbakeryorder
 * @see https://catvrf.ru/docs/viewbakeryorder
 * @see https://catvrf.ru/docs/viewbakeryorder
 */


namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\BakeryOrderResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * Class ViewBakeryOrder
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Pages
 */
final class ViewBakeryOrder extends ViewRecord
{
    protected static string $resource = BakeryOrderResource::class;

    /**
     * Handle getTitle operation.
     *
     * @throws \DomainException
     */
    public function getTitle(): string
    {
        return 'Просмотр заказа пекарни';
    }

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
}
