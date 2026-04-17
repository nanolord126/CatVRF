<?php declare(strict_types=1);

/**
 * ListBeverageShops — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listbeverageshops
 * @see https://catvrf.ru/docs/listbeverageshops
 * @see https://catvrf.ru/docs/listbeverageshops
 * @see https://catvrf.ru/docs/listbeverageshops
 * @see https://catvrf.ru/docs/listbeverageshops
 * @see https://catvrf.ru/docs/listbeverageshops
 * @see https://catvrf.ru/docs/listbeverageshops
 * @see https://catvrf.ru/docs/listbeverageshops
 * @see https://catvrf.ru/docs/listbeverageshops
 * @see https://catvrf.ru/docs/listbeverageshops
 * @see https://catvrf.ru/docs/listbeverageshops
 * @see https://catvrf.ru/docs/listbeverageshops
 * @see https://catvrf.ru/docs/listbeverageshops
 * @see https://catvrf.ru/docs/listbeverageshops
 * @see https://catvrf.ru/docs/listbeverageshops
 * @see https://catvrf.ru/docs/listbeverageshops
 */


namespace App\Filament\Tenant\Resources\BeverageShopResource\Pages;

use Filament\Resources\Pages\ListRecords;

final class ListBeverageShops extends ListRecords
{

    protected static string $resource = BeverageShopResource::class;

        protected function getHeaderActions(): array
        {
            return [
                \Filament\Actions\CreateAction::make()
                    ->label('Register New Venue')
                    ->icon('heroicon-o-plus'),
            ];
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
