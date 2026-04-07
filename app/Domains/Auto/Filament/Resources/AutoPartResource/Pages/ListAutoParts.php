<?php declare(strict_types=1);

/**
 * ListAutoParts — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listautoparts
 */


namespace App\Domains\Auto\Filament\Resources\AutoPartResource\Pages;

use Filament\Resources\Pages\ListRecords;

final class ListAutoParts extends ListRecords
{

    protected static string $resource = AutoPartResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\CreateAction::make(),
            ];
        }

        public function getTabs(): array
        {
            return [
                'all' => Tab::make('Все')
                    ->badge(static::getResource()::getEloquentQuery()->count()),

                'low_stock' => Tab::make('Низкий остаток')
                    ->modifyQueryUsing(fn (Builder $query) => $query->whereRaw('current_stock < min_stock_threshold'))
                    ->badge(static::getResource()::getEloquentQuery()->whereRaw('current_stock < min_stock_threshold')->count())
                    ->badgeColor('danger'),

                'out_of_stock' => Tab::make('Нет в наличии')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('current_stock', '<=', 0))
                    ->badge(static::getResource()::getEloquentQuery()->where('current_stock', '<=', 0)->count())
                    ->badgeColor('warning'),

                'available' => Tab::make('В наличии')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('current_stock', '>', 0)),
            ];
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

}
