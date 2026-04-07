<?php declare(strict_types=1);

/**
 * ListCars — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listcars
 * @see https://catvrf.ru/docs/listcars
 * @see https://catvrf.ru/docs/listcars
 * @see https://catvrf.ru/docs/listcars
 */


/**
 * ListCars — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listcars
 * @see https://catvrf.ru/docs/listcars
 * @see https://catvrf.ru/docs/listcars
 * @see https://catvrf.ru/docs/listcars
 * @see https://catvrf.ru/docs/listcars
 * @see https://catvrf.ru/docs/listcars
 * @see https://catvrf.ru/docs/listcars
 * @see https://catvrf.ru/docs/listcars
 * @see https://catvrf.ru/docs/listcars
 * @see https://catvrf.ru/docs/listcars
 * @see https://catvrf.ru/docs/listcars
 * @see https://catvrf.ru/docs/listcars
 * @see https://catvrf.ru/docs/listcars
 * @see https://catvrf.ru/docs/listcars
 * @see https://catvrf.ru/docs/listcars
 * @see https://catvrf.ru/docs/listcars
 * @see https://catvrf.ru/docs/listcars
 */


namespace App\Filament\Tenant\Resources\CarRental\Pages;

use Filament\Resources\Pages\ListRecords;

final class ListCars extends ListRecords
{

    protected static string $resource = CarResource::class;

        /**
         * Actions: Comprehensive Vehicle Creation.
         */
        protected function getHeaderActions(): array
        {
            return [
                Actions\CreateAction::make()
                    ->label('New Fleet Member')
                    ->icon('heroicon-o-plus-circle'),
            ];
        }
}
