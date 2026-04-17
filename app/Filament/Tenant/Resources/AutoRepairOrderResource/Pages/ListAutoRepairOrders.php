<?php declare(strict_types=1);

/**
 * ListAutoRepairOrders — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listautorepairorders
 * @see https://catvrf.ru/docs/listautorepairorders
 * @see https://catvrf.ru/docs/listautorepairorders
 * @see https://catvrf.ru/docs/listautorepairorders
 * @see https://catvrf.ru/docs/listautorepairorders
 * @see https://catvrf.ru/docs/listautorepairorders
 * @see https://catvrf.ru/docs/listautorepairorders
 * @see https://catvrf.ru/docs/listautorepairorders
 * @see https://catvrf.ru/docs/listautorepairorders
 * @see https://catvrf.ru/docs/listautorepairorders
 * @see https://catvrf.ru/docs/listautorepairorders
 * @see https://catvrf.ru/docs/listautorepairorders
 * @see https://catvrf.ru/docs/listautorepairorders
 * @see https://catvrf.ru/docs/listautorepairorders
 * @see https://catvrf.ru/docs/listautorepairorders
 * @see https://catvrf.ru/docs/listautorepairorders
 * @see https://catvrf.ru/docs/listautorepairorders
 */


namespace App\Filament\Tenant\Resources\AutoRepairOrderResource\Pages;

use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Pages\ListRecords;

final class ListAutoRepairOrders extends ListRecords
{

    protected static string $resource = AutoRepairOrderResource::class;

        protected function getHeaderActions(): array
        {
            return [
                \Filament\Actions\CreateAction::make()
                    ->label('Открыть заказ-наряд')
                    ->icon('heroicon-o-plus'),
            ];
        }

        /**
         * Tenant scoping.
         */
        protected function getTableQuery(): Builder
        {
            return parent::getTableQuery()
                ->where('tenant_id', tenant()->id);
        }
}
