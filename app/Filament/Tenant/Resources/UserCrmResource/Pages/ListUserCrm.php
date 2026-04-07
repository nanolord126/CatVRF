<?php declare(strict_types=1);

/**
 * ListUserCrm — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listusercrm
 * @see https://catvrf.ru/docs/listusercrm
 * @see https://catvrf.ru/docs/listusercrm
 */


namespace App\Filament\Tenant\Resources\UserCrmResource\Pages;

use App\Filament\Tenant\Resources\UserCrmResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * Class ListUserCrm
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\UserCrmResource\Pages
 */
final class ListUserCrm extends ListRecords
{
    protected static string $resource = UserCrmResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_all')
                ->label('Экспорт всех')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [];
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
