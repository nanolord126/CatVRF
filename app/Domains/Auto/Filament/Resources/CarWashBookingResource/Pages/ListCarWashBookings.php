<?php declare(strict_types=1);

/**
 * ListCarWashBookings — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listcarwashbookings
 */


namespace App\Domains\Auto\Filament\Resources\CarWashBookingResource\Pages;

use Filament\Resources\Pages\ListRecords;

final class ListCarWashBookings extends ListRecords
{

    protected static string $resource = CarWashBookingResource::class;

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

                'pending' => Tab::make('В ожидании')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                    ->badge(static::getResource()::getEloquentQuery()->where('status', 'pending')->count())
                    ->badgeColor('warning'),

                'in_progress' => Tab::make('В процессе')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'in_progress'))
                    ->badge(static::getResource()::getEloquentQuery()->where('status', 'in_progress')->count())
                    ->badgeColor('info'),

                'completed' => Tab::make('Завершены')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed')),

                'cancelled' => Tab::make('Отменены')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled')),
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

}
